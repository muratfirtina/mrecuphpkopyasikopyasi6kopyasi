<?php
/**
 * Mr ECU - Modern Kullanıcı Kredi Yönetimi Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/credits.php');
}

$user = new User($pdo);
$userId = $_SESSION['user_id'];

// TERS KREDİ SİSTEMİ: Kullanıcının kredi durumunu al
$stmt = $pdo->prepare("SELECT credit_quota, credit_used FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userCreditInfo = $stmt->fetch(PDO::FETCH_ASSOC);

$creditQuota = $userCreditInfo['credit_quota'] ?? 0;
$creditUsed = $userCreditInfo['credit_used'] ?? 0;
$availableCredits = $creditQuota - $creditUsed;

// Session'a kullanılabilir kredi bilgisini kaydet (eski sistemle uyumluluk için)
$_SESSION['credits'] = $availableCredits;

$error = '';
$success = '';

// Kredi yükleme işlemi (örnek - gerçek ödeme entegrasyonu gerekli)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_credits'])) {
    $amount = floatval($_POST['amount']);
    $paymentMethod = sanitize($_POST['payment_method']);
    
    if ($amount < 10) {
        $error = 'Minimum kredi yükleme tutarı 10 TL\'dir.';
    } elseif ($amount > 1000) {
        $error = 'Maksimum kredi yükleme tutarı 1000 TL\'dir.';
    } else {
        // Gerçek uygulamada burada ödeme gateway entegrasyonu olacak
        try {
            $stmt = $pdo->prepare("
                INSERT INTO credit_transactions (id, user_id, transaction_type, amount, description, created_at) 
                VALUES (?, ?, 'deposit', ?, ?, NOW())
            ");
            $stmt->execute([generateUUID(), $userId, $amount, "Kredi yükleme - $paymentMethod"]);
            
            // Kullanıcının kredisini güncelle
            $stmt = $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);
            
            // Session'ı güncelle
            $_SESSION['credits'] = $user->getUserCredits($userId);
            
            $success = number_format($amount, 2) . " TL kredi başarıyla hesabınıza yüklendi.";
            
        } catch(PDOException $e) {
            $error = 'Kredi yükleme sırasında hata oluştu.';
        }
    }
}

// Filtreleme parametreleri - URL'den al
$type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Sayfalama
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(5, min(100, intval($_GET['limit']))) : 10; // 5-100 arası limit


// Son kredi işlemlerini getir (filtreleme ile)
try {
    // Filtreleme parametreleri
    $whereClause = 'WHERE ct.user_id = ?';
    $params = [$userId];
    
    // Sadece boş olmayan filtreleri ekle
    if (!empty($type)) {
        $whereClause .= ' AND (ct.transaction_type = ? OR ct.type = ?)';
        $params[] = $type;
        $params[] = $type;
    }
    
    if (!empty($dateFrom)) {
        $whereClause .= ' AND DATE(ct.created_at) >= ?';
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $whereClause .= ' AND DATE(ct.created_at) <= ?';
        $params[] = $dateTo;
    }
    
    // Sayfalama
    $offset = ($page - 1) * $limit;
    
    // LIMIT ve OFFSET değerlerini integer'a çevir ve doğrula
    $limit = (int)$limit;
    $offset = (int)$offset;
    
    // Ana sorgu - LIMIT ve OFFSET'i direkt sorguya ekle (PDO parametresi olarak değil)
    $query = "
        SELECT ct.*, u.username as admin_username,
               CASE 
                   WHEN ct.transaction_type IS NOT NULL THEN ct.transaction_type
                   WHEN ct.type IS NOT NULL THEN ct.type
                   ELSE 'unknown'
               END as effective_type
        FROM credit_transactions ct
        LEFT JOIN users u ON ct.admin_id = u.id
        {$whereClause}
        ORDER BY ct.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ";
    
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params); // Sadece WHERE clause parametreleri
    $recentTransactions = $stmt->fetchAll();
    
    // Toplam sayı
    $countQuery = "
        SELECT COUNT(*) 
        FROM credit_transactions ct
        {$whereClause}
    ";
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $filteredTransactions = $countStmt->fetchColumn();
    $totalPages = ceil($filteredTransactions / $limit);
    
    
} catch(PDOException $e) {
    // Hata durumunda eski basit sorguyu kullan
    error_log('Credits transactions error: ' . $e->getMessage());
    
    try {
        $stmt = $pdo->prepare("
            SELECT ct.*, u.username as admin_username,
                   COALESCE(ct.transaction_type, ct.type, 'unknown') as effective_type
            FROM credit_transactions ct
            LEFT JOIN users u ON ct.admin_id = u.id
            WHERE ct.user_id = ?
            ORDER BY ct.created_at DESC
            LIMIT {$limit}
        ");
        $stmt->execute([$userId]);
        $recentTransactions = $stmt->fetchAll();
        $filteredTransactions = count($recentTransactions);
        $totalPages = 1;
        
        // Debug hata durumunu da göster
        if (isset($_GET['debug']) || !empty($type) || !empty($dateFrom) || !empty($dateTo)) {
            echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px; border: 2px solid #dc3545;'>";
            echo "<strong>FALLBACK QUERY USED (LIMIT $limit):</strong><br>";
            echo "Error: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "Returned: " . count($recentTransactions) . " transactions<br>";
            echo "</div>";
        }
        
        // Reset filters on error
        $type = '';
        $dateFrom = '';
        $dateTo = '';
        
    } catch(PDOException $e2) {
        error_log('Credits fallback error: ' . $e2->getMessage());
        $recentTransactions = [];
        $filteredTransactions = 0;
        $totalPages = 0;
    }
}

// İstatistikler
try {
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN transaction_type IN ('deposit', 'add') THEN amount ELSE 0 END) as total_loaded,
            SUM(CASE WHEN transaction_type IN ('withdraw', 'file_charge', 'deduct', 'purchase') THEN amount ELSE 0 END) as total_spent,
            COUNT(*) as total_transactions
        FROM credit_transactions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    
    $totalLoaded = $stats['total_loaded'] ?? 0;
    $totalSpent = $stats['total_spent'] ?? 0;
    $totalTransactions = $stats['total_transactions'] ?? 0;
    
    // Bu ay harcama
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) 
        FROM credit_transactions 
        WHERE user_id = ? 
        AND transaction_type IN ('withdraw', 'file_charge', 'deduct', 'purchase') 
        AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$userId]);
    $monthlySpent = $stmt->fetchColumn() ?: 0;
    
} catch(PDOException $e) {
    $totalLoaded = 0;
    $totalSpent = 0;
    $totalTransactions = 0;
    $monthlySpent = 0;
}

// Kredi paketleri
$creditPackages = [
    ['amount' => 25, 'bonus' => 0, 'price' => 25, 'popular' => false, 'savings' => 0],
    ['amount' => 50, 'bonus' => 5, 'price' => 50, 'popular' => false, 'savings' => 5],
    ['amount' => 100, 'bonus' => 15, 'price' => 100, 'popular' => true, 'savings' => 15],
    ['amount' => 200, 'bonus' => 40, 'price' => 200, 'popular' => false, 'savings' => 40],
    ['amount' => 500, 'bonus' => 125, 'price' => 500, 'popular' => false, 'savings' => 125]
];

$pageTitle = 'Kredi Yükle';

// Header include
include '../includes/user_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/user_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="bi bi-coin me-2 text-warning"></i>Kredi İşlemleri
                    </h1>
                    <p class="text-muted mb-0">Hesabınıza kredi yükleyin ve dosya işlemlerinizi gerçekleştirin</p>
                </div>
                <!-- <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="transactions.php" class="btn btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i>Tüm İşlemler
                        </a>
                    </div>
                </div> -->
            </div>

            <!-- Hata/Başarı Mesajları -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-modern alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle me-3 fa-lg"></i>
                        <div>
                            <strong>Hata!</strong> <?php echo $error; ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-modern alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle me-3 fa-lg"></i>
                        <div>
                            <strong>Başarılı!</strong> <?php echo $success; ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Kredi Durumu Banner - Ters Kredi Sistemi -->
            <div class="credit-banner mb-4">
                <div class="credit-content">
                    <div class="credit-info">
                        <h3 class="credit-title">Kredi Kullanım Durumunuz</h3>
                        
                        <!-- Kredi Progress Bar -->
                        <div class="credit-progress-container mb-3">
                            <div class="progress-header d-flex justify-content-between align-items-center mb-2">
                                <div class="progress-label">
                                    <span class="quota-info">Kredi Kotanız: <strong><?php echo number_format($creditQuota, 2); ?> TL</strong></span>
                                </div>
                                <div class="usage-percentage">
                                    <?php 
                                    $usagePercentage = $creditQuota > 0 ? ($creditUsed / $creditQuota) * 100 : 0;
                                    $remainingPercentage = 100 - $usagePercentage;
                                    ?>
                                    <span class="percentage-text"><?php echo number_format($usagePercentage, 1); ?>% Kullanıldı</span>
                                </div>
                            </div>
                            
                            <div class="credit-progress-bar">
                                <div class="progress" style="height: 24px; border-radius: 12px; background-color: #e8f5e8;">
                                    <div class="progress-bar progress-bar-used" 
                                         style="width: <?php echo $usagePercentage; ?>%; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-radius: 12px;" 
                                         data-bs-toggle="tooltip" 
                                         title="Kullanılan: <?php echo number_format($creditUsed, 2); ?> TL">
                                    </div>
                                    <div class="progress-bar progress-bar-remaining" 
                                         style="width: <?php echo $remainingPercentage; ?>%; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 12px;" 
                                         data-bs-toggle="tooltip" 
                                         title="Kalan: <?php echo number_format($availableCredits, 2); ?> TL">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="progress-footer d-flex justify-content-between mt-2">
                                <div class="used-info">
                                    <i class="bi bi-minus-circle text-danger me-1"></i>
                                    <span class="text-danger">Kullanılan: <strong><?php echo number_format($creditUsed, 2); ?> TL</strong></span>
                                </div>
                                <div class="remaining-info">
                                    <i class="bi bi-check-circle text-success me-1"></i>
                                    <span class="text-success">Kalan: <strong><?php echo number_format($availableCredits, 2); ?> TL</strong></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Mevcut Bakiye Gösterimi -->
                        <!-- <div class="current-balance-display">
                            <div class="balance-card">
                                <div class="balance-header">
                                    <i class="bi bi-wallet me-2"></i>
                                    <span>Kullanılabilir Bakiye</span>
                                </div>
                                <div class="balance-amount">
                                    <span class="amount"><?php echo number_format($availableCredits, 2); ?></span>
                                    <span class="currency">TL</span>
                                </div>
                                <div class="balance-status">
                                    <?php if ($availableCredits > 0): ?>
                                        <span class="status-badge status-active">
                                            <i class="bi bi-check-circle me-1"></i>Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-exhausted">
                                            <i class="bi bi-exclamation-triangle me-1"></i>Tükendi
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div> -->
                        
                        <!-- İstatistikler -->
                        <div class="credit-stats mt-3">
                            <div class="stat-item">
                                <i class="bi bi bar-chart-line text-info"></i>
                                <span>Toplam İşlem: <strong><?php echo $totalTransactions; ?></strong></span>
                            </div>
                            <div class="stat-item">
                                <i class="bi bi-calendar-alt text-warning"></i>
                                <span>Bu Ay: <strong><?php echo number_format($monthlySpent, 2); ?> TL</strong></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kredi Durumu Görseli -->
                    <div class="credit-visual">
                        <div class="credit-status-circle">
                            <div class="circle-progress" data-percentage="<?php echo $usagePercentage; ?>">
                                <div class="circle-inner">
                                    <div class="percentage-display">
                                        <span class="percentage"><?php echo number_format($remainingPercentage, 0); ?>%</span>
                                        <span class="label">Kalan</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="quota-info-visual mt-3">
                            <div class="quota-item">
                                <span class="quota-label">Toplam Kota</span>
                                <span class="quota-value"><?php echo number_format($creditQuota, 0); ?> TL</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İstatistik Kartları -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card credit">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-success"><?php echo number_format($totalLoaded, 2); ?></div>
                                    <div class="stat-label">Toplam Yüklenen</div>
                                    <div class="stat-trend">
                                        <i class="bi bi-plus-circle text-success"></i>
                                        <span class="text-success">Kredi TL</span>
                                    </div>
                                </div>
                                <div class="stat-icon text-success">
                                    <i class="bi bi-plus-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card credit">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-danger"><?php echo number_format($totalSpent, 2); ?></div>
                                    <div class="stat-label">Toplam Harcanan</div>
                                    <div class="stat-trend">
                                        <i class="bi bi-minus-circle text-danger"></i>
                                        <span class="text-danger">Kredi TL</span>
                                    </div>
                                </div>
                                <div class="stat-icon text-danger">
                                    <i class="bi bi-minus-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card credit">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-warning"><?php echo number_format($monthlySpent, 2); ?></div>
                                    <div class="stat-label">Bu Ay Harcanan</div>
                                    <div class="stat-trend">
                                        <i class="bi bi-calendar text-warning"></i>
                                        <span class="text-warning">Aylık kullanım</span>
                                    </div>
                                </div>
                                <div class="stat-icon text-warning">
                                    <i class="bi bi-calendar-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card credit">
                        <div class="stat-card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-number text-primary"><?php echo $totalTransactions; ?></div>
                                    <div class="stat-label">Toplam İşlem</div>
                                    <div class="stat-trend">
                                        <i class="bi bi-exchange-alt text-primary"></i>
                                        <span class="text-primary">Tüm zamanlar</span>
                                    </div>
                                </div>
                                <div class="stat-icon text-primary">
                                    <i class="bi bi-exchange-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Sol Kolon - Kredi Paketleri -->
                <div class="col-lg-8">
                    <!-- Kredi Paketleri -->
                    <!-- <div class="packages-section mb-4">
                        <div class="section-header">
                            <h4 class="mb-2">
                                <i class="bi bi-gift me-2 text-primary"></i>Kredi Paketleri
                            </h4>
                            <p class="text-muted">En uygun paketleri seçin ve bonus kredi kazanın</p>
                        </div>
                        
                        <div class="packages-grid">
                            <?php foreach ($creditPackages as $index => $package): ?>
                                <div class="package-card <?php echo $package['popular'] ? 'popular' : ''; ?>" 
                                     onclick="selectPackage(<?php echo $package['amount'] + $package['bonus']; ?>, <?php echo $package['price']; ?>)">
                                    
                                    <?php if ($package['popular']): ?>
                                        <div class="popular-badge">
                                            <i class="bi bi-star me-1"></i>POPÜLER
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="package-content">
                                        <div class="package-amount">
                                            <span class="main-amount"><?php echo $package['amount']; ?></span>
                                            <span class="currency">TL</span>
                                        </div>
                                        
                                        <?php if ($package['bonus'] > 0): ?>
                                            <div class="bonus-info">
                                                <div class="bonus-badge">
                                                    +<?php echo $package['bonus']; ?> TL Bonus
                                                </div>
                                                <div class="total-amount">
                                                    Toplam: <strong><?php echo $package['amount'] + $package['bonus']; ?> TL</strong>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="package-price">
                                            <span class="price"><?php echo number_format($package['price'], 2); ?> TL</span>
                                        </div>
                                        
                                        <?php if ($package['savings'] > 0): ?>
                                            <div class="savings-info">
                                                <i class="bi bi-piggy-bank me-1"></i>
                                                <?php echo number_format(($package['savings'] / $package['price']) * 100, 0); ?>% tasarruf
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="package-button">
                                            <span class="btn-text">
                                                <i class="bi bi-shopping-cart me-1"></i>Satın Al
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div> -->

                    <!-- Özel Tutar -->
                    <!-- <div class="custom-amount-section">
                        <div class="section-header">
                            <h4 class="mb-2">
                                <i class="bi bi-pencil-square me-2 text-info"></i>Özel Tutar
                            </h4>
                            <p class="text-muted">İstediğiniz tutarda kredi yükleyin</p>
                        </div>
                        
                        <div class="custom-amount-card">
                            <form method="POST" id="creditForm" class="credit-form">
                                <input type="hidden" name="add_credits" value="1">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="amount" class="form-label">
                                            <i class="bi bi-money-bill-wave me-1"></i>Kredi Tutarı
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control form-control-modern" 
                                                   id="amount" name="amount" 
                                                   min="10" max="1000" step="0.01" 
                                                   placeholder="Tutar girin" required>
                                            <span class="input-group-text">TL</span>
                                        </div>
                                        <div class="form-help">Minimum: 10 TL | Maksimum: 1000 TL</div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="payment_method" class="form-label">
                                            <i class="bi bi-credit-card me-1"></i>Ödeme Yöntemi
                                        </label>
                                        <select class="form-select form-control-modern" id="payment_method" name="payment_method" required>
                                            <option value="">Ödeme yöntemini seçin</option>
                                            <option value="credit_card">💳 Kredi Kartı</option>
                                            <option value="debit_card">💳 Banka Kartı</option>
                                            <option value="bank_transfer">🏦 Havale/EFT</option>
                                            <option value="paypal">💰 PayPal</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-success btn-modern btn-lg">
                                        <i class="bi bi-credit-card me-2"></i>
                                        Kredi Yükle
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div> -->

                    <!-- Son İşlemler -->
                    <div class="recent-transactions-section">
                        <div class="section-header">
                            <h4 class="mb-2">
                                <i class="bi bi-clock me-2 text-secondary"></i>Kredi İşlem Geçmişi
                                <?php if ($filteredTransactions > 0): ?>
                                    <span class="badge bg-primary ms-2"> Toplam <?php echo $filteredTransactions; ?></span>
                                <?php endif; ?>
                                <?php if ($filteredTransactions > $limit): ?>
                                <span class="btn btn-outline-info btn-sm disabled">
                                    <i class="bi bi-layers me-1"></i><?php echo $filteredTransactions - $limit; ?> daha
                                </span>
                                <?php endif; ?>
                            </h4>                       
                        </div>
                        
                        <!-- Filtre Formu -->
                        <div class="filter-card-compact mb-3">
                            <form method="GET" id="filterForm" class="row g-2 align-items-end">
                                <!-- Preserve existing GET parameters -->
                                <?php if (isset($_GET['page']) && $_GET['page'] != 1): ?>
                                    <input type="hidden" name="page" value="1">
                                <?php endif; ?>
                                
                                <div class="col-md-3">
                                    <label for="type_filter" class="form-label form-label-sm">
                                        <i class="bi bi-tag me-1"></i>İşlem Tipi
                                    </label>
                                    <select class="form-select form-select-sm" id="type_filter" name="type">
                                        <option value="">Tüm İşlemler</option>
                                        <option value="add" <?php echo $type === 'add' ? 'selected' : ''; ?>>Kredi Yükleme (Add)</option>
                                        <option value="withdraw" <?php echo $type === 'withdraw' ? 'selected' : ''; ?>>Kredi Kullanımı (Withdraw)</option>
                                        <option value="file_charge" <?php echo $type === 'file_charge' ? 'selected' : ''; ?>>Dosya Ücreti</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="date_from_filter" class="form-label form-label-sm">
                                        <i class="bi bi-calendar me-1"></i>Başlangıç
                                    </label>
                                    <input type="date" class="form-control form-control-sm" id="date_from_filter" name="date_from" 
                                           value="<?php echo htmlspecialchars($dateFrom); ?>">
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="date_to_filter" class="form-label form-label-sm">
                                        <i class="bi bi-calendar-check me-1"></i>Bitiş
                                    </label>
                                    <input type="date" class="form-control form-control-sm" id="date_to_filter" name="date_to" 
                                           value="<?php echo htmlspecialchars($dateTo); ?>">
                                </div>
                                
                                <div class="col-md-2">
                                    <label for="limit_filter" class="form-label form-label-sm">
                                        <i class="bi bi-list me-1"></i>Sayfa Başı
                                    </label>
                                    <select class="form-select form-select-sm" id="limit_filter" name="limit">
                                        <option value="5" <?php echo $limit === 5 ? 'selected' : ''; ?>>5</option>
                                        <option value="10" <?php echo $limit === 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="20" <?php echo $limit === 20 ? 'selected' : ''; ?>>20</option>
                                        <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-2">
                                    <div class="d-flex gap-1">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-search me-1"></i>Filtrele
                                        </button>
                                        <a href="credits.php" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-undo me-1"></i>Temizle
                                        </a>
                                        <?php if (isset($_GET['show_debug']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                                        <a href="credits.php?debug=1<?php echo $type ? '&type=' . urlencode($type) : ''; ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : ''; ?>&limit=<?php echo $limit; ?>" class="btn btn-outline-info btn-sm" title="Debug">
                                            <i class="bi bi-bug"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php if (empty($recentTransactions)): ?>
                            <div class="empty-transactions">
                                <div class="empty-icon">
                                    <i class="bi bi-receipt"></i>
                                </div>
                                <h6>
                                    <?php if ($type || $dateFrom || $dateTo): ?>
                                        Filtreye uygun işlem bulunamadı
                                    <?php else: ?>
                                        Henüz işlem bulunmuyor
                                    <?php endif; ?>
                                </h6>
                                <p class="text-muted">
                                    <?php if ($type || $dateFrom || $dateTo): ?>
                                        Farklı filtre kriterleri deneyebilirsiniz.
                                    <?php else: ?>
                                        İlk kredi yüklemenizi yaparak başlayabilirsiniz.
                                    <?php endif; ?>
                                </p>
                                <?php if (!($type || $dateFrom || $dateTo)): ?>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="selectPackage(50, 50)">
                                            <i class="bi bi-credit-card me-1"></i>Hızlı Kredi Yükle
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <!-- İşlem Listesi -->
                            <div class="transactions-list">
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <div class="transaction-item">
                                        <div class="transaction-icon">
                                            <?php
                                            $iconClass = 'bi bi-circle';
                                            $iconColor = 'secondary';
                                            
                                            $effectiveType = $transaction['effective_type'] ?? $transaction['transaction_type'] ?? $transaction['type'] ?? 'unknown';
                                            switch ($effectiveType) {
                                                case 'add':
                                                    $iconClass = 'bi bi-plus-circle';
                                                    $iconColor = 'success';
                                                    break;
                                                case 'deduct':
                                                case 'withdraw':
                                                case 'file_charge':
                                                    $iconClass = 'bi bi-minus-circle';
                                                    $iconColor = 'danger';
                                                    break;
                                                default:
                                                    $iconClass = 'bi bi-circle';
                                                    $iconColor = 'secondary';
                                                    break;
                                            }
                                            ?>
                                            <i class="<?php echo $iconClass; ?> text-<?php echo $iconColor; ?>"></i>
                                        </div>
                                        
                                        <div class="transaction-details">
                                            <div class="transaction-title">
                                                <?php
                                                $title = 'Bilinmeyen İşlem';
                                                switch ($effectiveType) {
                                                    case 'add':
                                                        $title = 'Kredi Yükleme';
                                                        break;
                                                    case 'deduct':
                                                    case 'withdraw':
                                                        $title = 'Kredi Kullanımı';
                                                        break;
                                                    case 'file_charge':
                                                        $title = 'Dosya Ücreti';
                                                        break;
                                                    default:
                                                        $title = $effectiveType ? ucfirst($effectiveType) : 'Bilinmeyen İşlem';
                                                        break;
                                                }
                                                echo $title;
                                                ?>
                                            </div>
                                            <div class="transaction-desc">
                                                <?php echo htmlspecialchars($transaction['description'] ?? 'Açıklama yok'); ?>
                                            </div>
                                            <div class="transaction-date">
                                                <i class="bi bi-calendar-alt me-1"></i>
                                                <?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?>
                                                <?php if ($transaction['admin_username']): ?>
                                                    <span class="admin-info ms-2">
                                                        <i class="bi bi-person-fill me-1"></i>
                                                        <?php echo htmlspecialchars($transaction['admin_username']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="transaction-amount">
                                            <?php 
                                            $isPositive = ($effectiveType === 'add');
                                            $amountPrefix = $isPositive ? '+' : '-';
                                            $amountColorClass = $isPositive ? 'success' : 'danger';
                                            ?>
                                            <span class="amount text-<?php echo $amountColorClass; ?>">
                                                <?php echo $amountPrefix; ?>
                                                <?php echo number_format($transaction['amount'], 2); ?> TL
                                            </span>
                                            <span class="badge bg-<?php echo $iconColor; ?> badge-sm">
                                                <?php 
                                                switch ($effectiveType) {
                                                    case 'add':
                                                        echo 'Yüklendi';
                                                        break;
                                                    case 'deduct':
                                                    case 'withdraw':
                                                    case 'file_charge':
                                                        echo 'Kullanıldı';
                                                        break;
                                                    default:
                                                        echo 'İşlem';
                                                        break;
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Sayfalama - TEST İÇİN ZORLA GÖSTERİM -->
                            <?php if ($filteredTransactions > 5): // Test için 5'ten büyükse göster ?>
                                <div class="pagination-compact mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="pagination-info">
                                        <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        <strong>PAGINATION TEST:</strong> 
                                        <?php 
                                        $startRecord = (($page - 1) * $limit) + 1;
                                            $endRecord = min($page * $limit, $filteredTransactions);
                                                echo "Gösterilen: $startRecord-$endRecord / $filteredTransactions işlem";
                                        echo " | Total Pages: $totalPages | Current: $page";
                                        ?>
                                    </small>
                                </div>
                                        <div class="pagination-jump">
                                            <form method="GET" class="d-flex align-items-center gap-2" style="margin: 0;">
                                                <!-- Preserve filters -->
                                                <?php if ($type): ?><input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>"><?php endif; ?>
                                                <?php if ($dateFrom): ?><input type="hidden" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>"><?php endif; ?>
                                                <?php if ($dateTo): ?><input type="hidden" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>"><?php endif; ?>
                                                <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                                                
                                                <small class="text-muted">Sayfa:</small>
                                                <input type="number" name="page" value="<?php echo $page; ?>" 
                                                       min="1" max="<?php echo $totalPages; ?>" 
                                                       class="form-control form-control-sm" 
                                                       style="width: 60px; font-size: 0.75rem;">
                                                <button type="submit" class="btn btn-sm btn-outline-primary" style="padding: 0.25rem 0.5rem;">
                                                    <i class="bi bi-arrow-right"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <!-- TEST PAGINATION - ZORLA GÖSTER -->
                                    <nav aria-label="Test Sayfalama" class="pagination-nav">
                                        <ul class="pagination pagination-sm mb-0 justify-content-center">
                                            <!-- Test Previous -->
                                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo max(1, $page - 1); ?><?php echo $type ? '&type=' . urlencode($type) : ''; ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : ''; ?>&limit=<?php echo $limit; ?>" title="Önceki sayfa">
                                                    <i class="bi bi-chevron-left"></i> Önceki
                                                </a>
                                            </li>
                                            
                                            <!-- Test Current Page -->
                                            <li class="page-item active">
                                                <span class="page-link bg-primary text-white">Sayfa <?php echo $page; ?></span>
                                            </li>
                                            
                                            <!-- Test Next -->
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $type ? '&type=' . urlencode($type) : ''; ?><?php echo $dateFrom ? '&date_from=' . urlencode($dateFrom) : ''; ?><?php echo $dateTo ? '&date_to=' . urlencode($dateTo) : ''; ?>&limit=<?php echo $limit; ?>" title="Sonraki sayfa">
                                                    Sonraki <i class="bi bi-chevron-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            <?php elseif ($filteredTransactions > 0): ?>
                                <div class="pagination-compact mt-3">
                                    <div class="text-center">
                                        <small class="text-muted">
                                            <?php if ($filteredTransactions <= $limit): ?>
                                            <i class="bi bi-check-circle text-success me-1"></i>
                                            Tüm işlemler gösteriliyor (<?php echo $filteredTransactions; ?> işlem)
                                            <?php else: ?>
                                            <i class="bi bi-info-circle text-primary me-1"></i>
                                            Sadece son <?php echo $limit; ?> işlem gösteriliyor. 
                                            <a href="transactions.php" class="text-primary text-decoration-none">
                                                Tüm <?php echo $filteredTransactions; ?> işlemi gör
                                            </a>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sağ Kolon - Bilgilendirme -->
                <div class="col-lg-4">

                    <!-- Destek & İletişim -->
                    <div class="info-card">
                        <div class="info-header">
                            <h5 class="mb-0">
                                <i class="bi bi-headset me-2"></i>Destek & Yardım
                            </h5>
                        </div>
                        <div class="info-body">
                            <p class="text-muted mb-3">Kredi yükleme konusunda yardıma mı ihtiyacınız var?</p>
                            
                            <div class="support-options">
                                <a href="mailto:<?php echo SITE_EMAIL; ?>" class="support-option">
                                    <i class="bi bi-envelope"></i>
                                    <span>E-posta Gönder</span>
                                </a>
                                
                                <a href="tel:+905551234567" class="support-option">
                                    <i class="bi bi-telephone-fill"></i>
                                    <span>Telefon Desteği</span>
                                </a>
                                
                                <!-- <button class="support-option" onclick="openWhatsApp()">
                                    <i class="bi bi-whatsapp"></i>
                                    <span>WhatsApp</span>
                                </button> -->
                                
                                <a href="#" class="support-option" onclick="openLiveChat()">
                                    <i class="bi bi-comments"></i>
                                    <span>Canlı Destek</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* Modern Credits Page Styles - Ters Kredi Sistemi */
.credit-banner {
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    border-radius: 20px;
    padding: 2rem;
    color: white;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
}

.credit-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
}

.credit-info {
    flex: 1;
}

.credit-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    opacity: 0.9;
}

/* Kredi Progress Container */
.credit-progress-container {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.progress-header {
    font-size: 0.9rem;
    opacity: 0.9;
}

.quota-info {
    font-weight: 500;
}

.usage-percentage .percentage-text {
    font-weight: 600;
    font-size: 0.95rem;
}

.credit-progress-bar {
    position: relative;
    margin: 1rem 0;
}

.credit-progress-bar .progress {
    background-color: rgba(255, 255, 255, 0.2) !important;
    border: 1px solid rgba(255, 255, 255, 0.3);
    overflow: hidden;
}

.progress-bar-used {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
    transition: all 0.3s ease;
}

.progress-bar-remaining {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
    transition: all 0.3s ease;
}

.progress-footer {
    font-size: 0.85rem;
    opacity: 0.95;
}

.used-info, .remaining-info {
    font-weight: 500;
}

/* Mevcut Bakiye Kartı */
.current-balance-display {
    margin-top: 1.5rem;
}

.balance-card {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.25);
}

.balance-header {
    font-size: 0.9rem;
    opacity: 0.8;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.balance-amount {
    display: flex;
    align-items: baseline;
    justify-content: center;
    margin-bottom: 0.75rem;
}

.balance-amount .amount {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1;
}

.balance-amount .currency {
    font-size: 1.2rem;
    font-weight: 500;
    margin-left: 0.5rem;
    opacity: 0.8;
}

.balance-status {
    margin-top: 0.5rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.status-exhausted {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.3);
}

/* İstatistikler */
.credit-stats {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    opacity: 0.9;
    font-size: 0.9rem;
}

.stat-item i {
    margin-right: 0.5rem;
}

/* Kredi Görseli */
.credit-visual {
    min-width: 200px;
    text-align: center;
}

.credit-status-circle {
    position: relative;
    width: 140px;
    height: 140px;
    margin: 0 auto;
}

.circle-progress {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    background: conic-gradient(
        from 0deg,
        #dc3545 0deg,
        #dc3545 calc(var(--used-percentage, 0) * 3.6deg),
        #28a745 calc(var(--used-percentage, 0) * 3.6deg),
        #28a745 360deg
    );
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    animation: rotate 2s ease-in-out;
}

.circle-inner {
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.percentage-display {
    text-align: center;
    color: #495057;
}

.percentage {
    font-size: 1.8rem;
    font-weight: 700;
    display: block;
    line-height: 1;
}

.label {
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
}

.quota-info-visual {
    margin-top: 1rem;
}

.quota-item {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.quota-label {
    font-size: 0.8rem;
    opacity: 0.8;
    font-weight: 500;
}

.quota-value {
    font-size: 1.1rem;
    font-weight: 700;
}

@keyframes rotate {
    from {
        transform: rotate(-90deg);
    }
    to {
        transform: rotate(0deg);
    }
}

/* Stat Cards */
.stat-card.credit {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: none;
    overflow: hidden;
}

.stat-card.credit:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

/* Section Headers */
.section-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h4 {
    margin: 0;
    flex: 1;
}

/* Package Grid */
.packages-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.package-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.package-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    border-color: #667eea;
}

.package-card.popular {
    border-color: #ffc107;
    box-shadow: 0 4px 20px rgba(255, 193, 7, 0.3);
}

.popular-badge {
    position: absolute;
    top: -1px;
    left: -1px;
    right: -1px;
    background: #ffc107;
    color: #212529;
    padding: 0.5rem;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
}

.package-content {
    padding-top: 1rem;
}

.package-amount {
    display: flex;
    justify-content: center;
    align-items: baseline;
    margin-bottom: 1rem;
}

.main-amount {
    font-size: 2.5rem;
    font-weight: 700;
    color: #495057;
}

.bonus-info {
    margin-bottom: 1rem;
}

.bonus-badge {
    background: #28a745;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: inline-block;
}

.total-amount {
    color: #6c757d;
    font-size: 0.9rem;
}

.package-price {
    margin-bottom: 1rem;
}

.price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #495057;
}

.savings-info {
    color: #28a745;
    font-size: 0.85rem;
    font-weight: 500;
    margin-bottom: 1rem;
}

.package-button {
    background: #667eea;
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.package-card:hover .package-button {
    background: #5a67d8;
    transform: translateY(-1px);
}

/* Custom Amount Section */
.custom-amount-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.credit-form {
    max-width: 100%;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 0;
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.form-control-modern {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-help {
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.form-actions {
    text-align: center;
}

/* Recent Transactions */
.empty-transactions {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.empty-icon {
    font-size: 3rem;
    color: #e9ecef;
    margin-bottom: 1rem;
}

.transactions-list {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.transaction-item {
    display: flex;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.transaction-item:last-child {
    border-bottom: none;
}

.transaction-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.25rem;
}

.transaction-details {
    flex: 1;
}

.transaction-title {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
}

.transaction-desc {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.transaction-date {
    font-size: 0.8rem;
    color: #9ca3af;
}

.transaction-amount {
    text-align: right;
}

.transaction-amount .amount {
    font-size: 1.1rem;
    font-weight: 700;
}

/* Info Cards */
.info-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.info-header {
    background: #f8f9fa;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.info-body {
    padding: 1.5rem;
}

/* Price List */
.price-list {
    margin-bottom: 1rem;
}

.price-item {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.price-item:last-child {
    border-bottom: none;
}

.service-info {
    display: flex;
    align-items: center;
    flex: 1;
}

.service-info i {
    margin-right: 0.75rem;
    width: 20px;
}

.service-price {
    font-weight: 700;
    color: #495057;
}

.price-note {
    background: #fff3cd;
    padding: 0.75rem;
    border-radius: 8px;
    color: #856404;
    font-size: 0.8rem;
}

/* Security Features */
.security-features {
    margin-bottom: 1.5rem;
}

.security-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
}

.security-item i {
    margin-right: 0.75rem;
    width: 20px;
}

.security-badge {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

/* Support Options */
.support-options {
    display: grid;
    gap: 0.75rem;
}

.support-option {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    color: #495057;
    transition: all 0.3s ease;
    cursor: pointer;
    width: 100%;
}

.support-option:hover {
    background: #e9ecef;
    color: #495057;
    text-decoration: none;
    transform: translateX(2px);
}

.support-option i {
    margin-right: 0.75rem;
    width: 20px;
    color: #667eea;
}

/* Progress Bar Animasyonları */
.progress-bar-used,
.progress-bar-remaining {
    transition: width 1s ease-in-out;
}

/* Hover Efektleri */
.balance-card:hover {
    transform: translateY(-2px);
    transition: transform 0.3s ease;
}

.credit-progress-container:hover {
    background: rgba(255, 255, 255, 0.15);
    transition: background 0.3s ease;
}

/* Tooltip Stilleri */
.progress-bar[data-bs-toggle="tooltip"] {
    cursor: help;
}

/* Responsive İyileştirmeler */
@media (max-width: 767.98px) {
    .credit-content {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
    }
    
    .credit-visual {
        min-width: auto;
    }
    
    .circle-progress {
        width: 120px;
        height: 120px;
    }
    
    .circle-inner {
        width: 85px;
        height: 85px;
    }
    
    .percentage {
        font-size: 1.5rem;
    }
    
    .balance-amount .amount {
        font-size: 2rem;
    }
    
    .credit-progress-container {
        padding: 1rem;
    }
    
    .credit-stats {
        flex-direction: column;
        gap: 1rem;
        text-align: left;
    }
    
    .progress-footer {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .packages-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
}

/* Compact Filter Styles */
.filter-card-compact {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1rem;
    border: 1px solid #e9ecef;
}

.form-label-sm {
    font-size: 0.8rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
}

.form-select-sm, .form-control-sm {
    font-size: 0.85rem;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    border: 1px solid #ced4da;
    transition: all 0.3s ease;
}

.form-select-sm:focus, .form-control-sm:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Enhanced Transaction Items */
.transaction-item {
    transition: all 0.3s ease;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    padding: 1rem 0.75rem;
}

.transaction-item:hover {
    background: #f8f9fa;
    transform: translateX(2px);
}

.transaction-date {
    font-size: 0.8rem;
    color: #9ca3af;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.admin-info {
    font-size: 0.75rem;
    color: #6c757d;
    font-style: italic;
}

.badge-sm {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    margin-top: 0.25rem;
    display: inline-block;
}

/* Compact Pagination */
.pagination-compact {
    background: white;
    border-radius: 8px;
    padding: 0.75rem;
    border: 1px solid #e9ecef;
}

.pagination-info {
    flex: 1;
}

.pagination-sm .page-link {
    padding: 0.375rem 0.5rem;
    font-size: 0.8rem;
    border-radius: 4px;
    margin: 0 1px;
}

.pagination-sm .page-item.active .page-link {
    background-color: #667eea;
    border-color: #667eea;
}

/* Enhanced Empty State */
.empty-transactions {
    text-align: center;
    padding: 2rem 1rem;
    background: white;
    border-radius: 12px;
    border: 2px dashed #e9ecef;
}

.empty-transactions .empty-icon {
    font-size: 2.5rem;
    color: #dee2e6;
    margin-bottom: 1rem;
}

.empty-transactions h6 {
    color: #495057;
    margin-bottom: 0.75rem;
}

.empty-transactions p {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0;
}

/* Filter Active States */
.form-select-sm:not([value=""]),
.form-control-sm:not([value=""]) {
    border-color: #667eea;
    background-color: #f0f4ff;
}

/* Button Variants */
.btn-sm {
    font-size: 0.8rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
}

/* Responsive Enhancements */
@media (max-width: 767.98px) {
    .filter-card-compact {
        padding: 0.75rem;
    }
    
    .filter-card-compact .row {
        margin: 0;
    }
    
    .filter-card-compact .col-md-3 {
        padding: 0.25rem;
    }
    
    .pagination-compact {
        padding: 0.5rem;
    }
    
    .pagination-compact .d-flex {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .transaction-date {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .admin-info {
        margin-left: 0 !important;
    }
    
    .pagination-jump {
        width: 100%;
        justify-content: center;
    }
    
    .pagination-nav .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
}

/* Enhanced Pagination Styles */
.pagination-nav {
    margin-top: 1rem;
}

.pagination-jump {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
}

.pagination-compact {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.page-link {
    border-radius: 6px !important;
    margin: 0 2px;
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
}

.page-link:hover {
    background-color: #667eea;
    border-color: #667eea;
    color: white;
    transform: translateY(-1px);
}

.page-item.active .page-link {
    background-color: #667eea;
    border-color: #667eea;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

/* Quick Actions */
.filter-badge {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.8); }
    to { opacity: 1; transform: scale(1); }
}

/* Filter Active Indicators */
.form-select-sm:not([value=""]):not([value="0"]),
.form-control-sm:not([value=""]) {
    border-color: #667eea;
    background-color: #f0f4ff;
    font-weight: 500;
}

/* Loading States */
.transaction-loading {
    opacity: 0.6;
    pointer-events: none;
    position: relative;
}

.transaction-loading::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
}
</style>

<script>
// Package Selection
function selectPackage(amount, price) {
    document.getElementById('amount').value = amount;
    
    // Paket seçildiğinde ödeme yöntemini otomatik kredi kartı yap
    document.getElementById('payment_method').value = 'credit_card';
    
    // Scroll to form
    document.querySelector('.custom-amount-section').scrollIntoView({
        behavior: 'smooth',
        block: 'center'
    });


    
    // Highlight form
    const form = document.querySelector('.custom-amount-card');
    form.style.border = '2px solid #667eea';
    form.style.transform = 'scale(1.02)';
    
    setTimeout(() => {
        form.style.border = '';
        form.style.transform = '';
    }, 2000);
    
    showToast(amount + ' TL\'lik paket seçildi!', 'success');
}

// WhatsApp Support
function openWhatsApp() {
    const message = 'Merhaba, kredi yükleme konusunda yardıma ihtiyacım var.';
    const phoneNumber = '905551234567';
    const url = 'https://wa.me/' + phoneNumber + '?text=' + encodeURIComponent(message);
    window.open(url, '_blank');
}

// Live Chat
function openLiveChat() {
    // Live chat implementation
    alert('Canlı destek yakında aktif olacak!');
}

// Form Validation
document.getElementById('creditForm').addEventListener('submit', function(e) {
    const amount = parseFloat(document.getElementById('amount').value);
    const paymentMethod = document.getElementById('payment_method').value;
    
    if (amount < 10) {
        e.preventDefault();
        showToast('Minimum kredi yükleme tutarı 10 TL\'dir!', 'error');
        return false;
    }
    
    if (amount > 1000) {
        e.preventDefault();
        showToast('Maksimum kredi yükleme tutarı 1000 TL\'dir!', 'error');
        return false;
    }
    
    if (!paymentMethod) {
        e.preventDefault();
        showToast('Lütfen ödeme yöntemini seçin!', 'error');
        return false;
    }
    
    // Confirmation
    if (!confirm(`Hesabınıza ${amount.toFixed(2)} TL kredi yüklemek istediğinizden emin misiniz?`)) {
        e.preventDefault();
        return false;
    }
});

// Amount Input Validation
document.getElementById('amount').addEventListener('input', function() {
    const value = parseFloat(this.value);
    const formGroup = this.closest('.form-group');
    
    if (value && value >= 10 && value <= 1000) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    } else {
        this.classList.remove('is-valid');
        if (this.value) {
            this.classList.add('is-invalid');
        }
    }
});

// Package Cards Animation
document.querySelectorAll('.package-card').forEach(function(card) {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-8px)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});

// Circle Progress Animation
document.addEventListener('DOMContentLoaded', function() {
    const circleProgress = document.querySelector('.circle-progress');
    if (circleProgress) {
        const usagePercentage = circleProgress.getAttribute('data-percentage') || 0;
        
        // CSS custom property olarak kullanım yüzdesini ayarla
        circleProgress.style.setProperty('--used-percentage', usagePercentage);
        
        // Progress bar'ları animasyon ile yükle
        setTimeout(() => {
            const progressBars = document.querySelectorAll('.progress-bar-used, .progress-bar-remaining');
            progressBars.forEach(bar => {
                bar.style.animation = 'progressLoad 1.5s ease-out';
            });
        }, 500);
    }
    
    // Tooltip'leri aktifleştir
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (typeof bootstrap !== 'undefined') {
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Balance card hover animasyonu
    const balanceCard = document.querySelector('.balance-card');
    if (balanceCard) {
        balanceCard.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px) scale(1.02)';
        });
        
        balanceCard.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    }
});

// Progress bar animasyon CSS'i dinamik olarak ekle
const style = document.createElement('style');
style.textContent = `
    @keyframes progressLoad {
        from {
            width: 0 !important;
        }
        to {
            width: var(--final-width) !important;
        }
    }
`;
document.head.appendChild(style);

// Toast Notification Function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    toast.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 5000);
}

// AJAX Filtreleme için JavaScript desteği
document.addEventListener('DOMContentLoaded', function() {
    // Kredi durumu circle progress animasyonu
    initCreditProgressAnimation();
    
    function initCreditProgressAnimation() {
        const circleProgress = document.querySelector('.circle-progress');
        if (circleProgress) {
            const usagePercentage = parseFloat(circleProgress.getAttribute('data-percentage')) || 0;
            
            // Circle gradient'ini ayarla
            const usageDegrees = (usagePercentage / 100) * 360;
            const gradient = `conic-gradient(
                from 0deg,
                #dc3545 0deg,
                #dc3545 ${usageDegrees}deg,
                #28a745 ${usageDegrees}deg,
                #28a745 360deg
            )`;
            
            circleProgress.style.background = gradient;
            
            // Animasyon ile göster
            circleProgress.style.opacity = '0';
            circleProgress.style.transform = 'scale(0.8) rotate(-90deg)';
            
            setTimeout(() => {
                circleProgress.style.transition = 'all 1s ease-out';
                circleProgress.style.opacity = '1';
                circleProgress.style.transform = 'scale(1) rotate(0deg)';
            }, 300);
        }
        
        // Progress bar animasyonu
        const progressBars = document.querySelectorAll('.progress-bar-used, .progress-bar-remaining');
        progressBars.forEach((bar, index) => {
            const finalWidth = bar.style.width;
            bar.style.width = '0%';
            
            setTimeout(() => {
                bar.style.transition = 'width 1.2s ease-out';
                bar.style.width = finalWidth;
            }, 500 + (index * 200));
        });
    }
    // Filtre formu AJAX handling - DEVREDIŞItedBIRAKILDI
    // Normal form submit kullanılıyor
    /*
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Sayfa yenilenmesini engelle
            performAjaxFilterInternal();
        });
        
        // Auto-filter on select change (without page reload)
        const typeSelect = document.getElementById('type_filter');
        const dateFromInput = document.getElementById('date_from_filter');
        const dateToInput = document.getElementById('date_to_filter');
        
        if (typeSelect) {
            typeSelect.addEventListener('change', function() {
                performAjaxFilterInternal();
            });
        }
        
        if (dateFromInput) {
            dateFromInput.addEventListener('change', function() {
                if (this.value) {
                    performAjaxFilterInternal();
                }
            });
        }
        
        if (dateToInput) {
            dateToInput.addEventListener('change', function() {
                if (this.value) {
                    performAjaxFilterInternal();
                }
            });
        }
    }
    */
    
    // AJAX Filtreleme Fonksiyonu
    function performAjaxFilterInternal() {
        const form = document.getElementById('filterForm');
        const formData = new FormData(form);
        
        // Loading state göster
        showFilterLoading(true);
        
        // AJAX request
        fetch('credits_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.text(); // Önce text olarak al
        })
        .then(text => {
            console.log('Raw response:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('Parsed data:', data);
                
                if (data.success) {
                    // Sonuçları güncelle
                    updateTransactionsList(data.transactions);
                    updatePagination(data.pagination);
                    updateFilterInfo(data.info);
                    showToast('Filtre başarıyla uygulandı!', 'success');
                } else {
                    console.error('Server error:', data.error);
                    if (data.debug) {
                        console.error('Debug info:', data.debug);
                    }
                    showToast('Hata: ' + (data.error || 'Bilinmeyen hata'), 'error');
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', text);
                showToast('Sunucu yanıtı geçersiz!', 'error');
            }
        })
        .catch(error => {
            console.error('Ajax Error:', error);
            showToast('Bağlantı hatası: ' + error.message, 'error');
        })
        .finally(() => {
            showFilterLoading(false);
        });
    }
    
    // Global fonksiyonları ayarla
    window.filterFormInstance = filterForm;
    window.performAjaxFilterFunction = performAjaxFilterInternal;
    
    // Loading durumunu göster/gizle
    function showFilterLoading(show) {
        const submitBtn = document.querySelector('#filterForm button[type="submit"]');
        const transactionsList = document.querySelector('.transactions-list');
        
        if (show) {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-spinner fa-spin me-1"></i>Filtreliyor...';
            }
            if (transactionsList) {
                transactionsList.classList.add('transaction-loading');
            }
        } else {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-search me-1"></i>Filtrele';
            }
            if (transactionsList) {
                transactionsList.classList.remove('transaction-loading');
            }
        }
    }
    
    // İşlem listesini güncelle
    function updateTransactionsList(transactions) {
        const container = document.querySelector('.recent-transactions-section');
        const listContainer = container.querySelector('.transactions-list') || container.querySelector('.empty-transactions');
        
        if (transactions.length === 0) {
            // Boş durum göster
            listContainer.outerHTML = `
                <div class="empty-transactions">
                    <div class="empty-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <h6>Filtreye uygun işlem bulunamadı</h6>
                    <p class="text-muted">Farklı filtre kriterleri deneyebilirsiniz.</p>
                </div>
            `;
        } else {
            // İşlem listesini oluştur
            let html = '<div class="transactions-list">';
            
            transactions.forEach(function(transaction) {
                const effectiveType = transaction.effective_type || transaction.transaction_type || transaction.type || 'unknown';
                const isPositive = ['add', 'deposit'].includes(effectiveType);
                const iconClass = isPositive ? 'bi bi-plus-circle' : 'bi bi-minus-circle';
                const iconColor = isPositive ? 'success' : 'danger';
                const title = isPositive ? 'Kredi Yükleme' : 'Kredi Kullanımı';
                const badge = isPositive ? 'Yüklendi' : 'Kullanıldı';
                const amountPrefix = isPositive ? '+' : '-';
                
                html += `
                    <div class="transaction-item">
                        <div class="transaction-icon">
                            <i class="${iconClass} text-${iconColor}"></i>
                        </div>
                        <div class="transaction-details">
                            <div class="transaction-title">${title}</div>
                            <div class="transaction-desc">${transaction.description || 'Açıklama yok'}</div>
                            <div class="transaction-date">
                                <i class="bi bi-calendar-alt me-1"></i>
                                ${formatDate(transaction.created_at)}
                                ${transaction.admin_username ? `<span class="admin-info ms-2"><i class="bi bi-person-fill me-1"></i>${transaction.admin_username}</span>` : ''}
                            </div>
                        </div>
                        <div class="transaction-amount">
                            <span class="amount text-${iconColor}">
                                ${amountPrefix}${parseFloat(transaction.amount).toFixed(2)} TL
                            </span>
                            <span class="badge bg-${iconColor} badge-sm">${badge}</span>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            listContainer.outerHTML = html;
        }
    }
    
    // Sayfalama güncelle
    function updatePagination(pagination) {
        const paginationContainer = document.querySelector('.pagination-compact');
        if (paginationContainer && pagination.html) {
            paginationContainer.outerHTML = pagination.html;
        }
    }
    
    // Filtre bilgisini güncelle
    function updateFilterInfo(info) {
        const badge = document.querySelector('.section-header .badge');
        if (badge) {
            badge.textContent = info.total;
        } else if (info.total > 0) {
            const header = document.querySelector('.section-header h4');
            if (header && !header.querySelector('.badge')) {
                header.innerHTML += `<span class="badge bg-primary ms-2">${info.total}</span>`;
            }
        }
    }
    
    // Tarih formatla
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('tr-TR', {
            day: '2-digit',
            month: '2-digit', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Sayfalama form handling
    const paginationForms = document.querySelectorAll('.pagination-jump form');
    paginationForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const pageInput = this.querySelector('input[name="page"]');
            if (pageInput) {
                const pageNum = parseInt(pageInput.value);
                const maxPage = parseInt(pageInput.getAttribute('max'));
                
                if (pageNum < 1 || pageNum > maxPage) {
                    e.preventDefault();
                    showToast(`Sayfa numarası 1 ile ${maxPage} arasında olmalıdır!`, 'error');
                    return false;
                }
            }
        });
    });
    
    // Pagination links smooth scroll
    const paginationLinks = document.querySelectorAll('.pagination-nav a');
    paginationLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            const transactionSection = document.querySelector('.recent-transactions-section');
            if (transactionSection) {
                // Scroll to top of section
                setTimeout(() => {
                    transactionSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        });
    });
    
    // Enhanced transaction hover
    const transactionItems = document.querySelectorAll('.transaction-item');
    transactionItems.forEach(function(item) {
        item.addEventListener('mouseenter', function() {
            this.style.borderLeft = '3px solid #667eea';
            this.style.paddingLeft = '0.7rem';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.borderLeft = '';
            this.style.paddingLeft = '0.75rem';
        });
    });
});

// Global sayfalama fonksiyonları
window.changePage = function(page) {
    if (window.filterFormInstance) {
        // Page parametresini ekle
        let pageInput = window.filterFormInstance.querySelector('input[name="page"]');
        if (!pageInput) {
            pageInput = document.createElement('input');
            pageInput.type = 'hidden';
            pageInput.name = 'page';
            window.filterFormInstance.appendChild(pageInput);
        }
        pageInput.value = page;
        
        // Filtreleme yap
        if (window.performAjaxFilterFunction) {
            window.performAjaxFilterFunction();
        }
    }
};

window.jumpToPage = function(page) {
    const pageNum = parseInt(page);
    if (pageNum && pageNum > 0) {
        changePage(pageNum);
    }
};
</script>

<?php
// Footer include
include '../includes/user_footer.php';
?>