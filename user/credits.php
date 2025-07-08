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

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($userId);

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
                INSERT INTO credit_transactions (user_id, transaction_type, amount, description, created_at) 
                VALUES (?, 'add', ?, ?, NOW())
            ");
            $stmt->execute([$userId, $amount, "Kredi yükleme - $paymentMethod"]);
            
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

// Son kredi işlemlerini getir
try {
    $stmt = $pdo->prepare("
        SELECT ct.*, u.username as admin_username 
        FROM credit_transactions ct
        LEFT JOIN users u ON ct.admin_id = u.id
        WHERE ct.user_id = ?
        ORDER BY ct.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $recentTransactions = $stmt->fetchAll();
} catch(PDOException $e) {
    $recentTransactions = [];
}

// İstatistikler
try {
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN transaction_type = 'add' THEN amount ELSE 0 END) as total_loaded,
            SUM(CASE WHEN transaction_type = 'deduct' THEN amount ELSE 0 END) as total_spent,
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
        SELECT SUM(amount) 
        FROM credit_transactions 
        WHERE user_id = ? 
        AND transaction_type = 'deduct' 
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
        <?php include '_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="fas fa-coins me-2 text-warning"></i>Kredi Yükle
                    </h1>
                    <p class="text-muted mb-0">Hesabınıza kredi yükleyin ve dosya işlemlerinizi gerçekleştirin</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="transactions.php" class="btn btn-outline-primary">
                            <i class="fas fa-history me-1"></i>Tüm İşlemler
                        </a>
                    </div>
                </div>
            </div>

            <!-- Hata/Başarı Mesajları -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-modern alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
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
                        <i class="fas fa-check-circle me-3 fa-lg"></i>
                        <div>
                            <strong>Başarılı!</strong> <?php echo $success; ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Kredi Durumu Banner -->
            <div class="credit-banner mb-4">
                <div class="credit-content">
                    <div class="credit-info">
                        <h3 class="credit-title">Mevcut Kredi Bakiyeniz</h3>
                        <div class="credit-amount">
                            <span class="amount"><?php echo number_format($_SESSION['credits'], 2); ?></span>
                            <span class="currency">TL</span>
                        </div>
                        <div class="credit-stats">
                            <div class="stat-item">
                                <i class="fas fa-arrow-up text-success"></i>
                                <span>Toplam Yüklenen: <strong><?php echo number_format($totalLoaded, 2); ?> TL</strong></span>
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-arrow-down text-danger"></i>
                                <span>Toplam Harcanan: <strong><?php echo number_format($totalSpent, 2); ?> TL</strong></span>
                            </div>
                        </div>
                    </div>
                    <div class="credit-visual">
                        <div class="credit-icon">
                            <i class="fas fa-wallet"></i>
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
                                        <i class="fas fa-plus-circle text-success"></i>
                                        <span class="text-success">Kredi TL</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-success">
                                    <i class="fas fa-plus-circle"></i>
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
                                        <i class="fas fa-minus-circle text-danger"></i>
                                        <span class="text-danger">Kredi TL</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-danger">
                                    <i class="fas fa-minus-circle"></i>
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
                                        <i class="fas fa-calendar text-warning"></i>
                                        <span class="text-warning">Aylık kullanım</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-warning">
                                    <i class="fas fa-calendar-alt"></i>
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
                                        <i class="fas fa-exchange-alt text-primary"></i>
                                        <span class="text-primary">Tüm zamanlar</span>
                                    </div>
                                </div>
                                <div class="stat-icon bg-primary">
                                    <i class="fas fa-exchange-alt"></i>
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
                    <div class="packages-section mb-4">
                        <div class="section-header">
                            <h4 class="mb-2">
                                <i class="fas fa-gift me-2 text-primary"></i>Kredi Paketleri
                            </h4>
                            <p class="text-muted">En uygun paketleri seçin ve bonus kredi kazanın</p>
                        </div>
                        
                        <div class="packages-grid">
                            <?php foreach ($creditPackages as $index => $package): ?>
                                <div class="package-card <?php echo $package['popular'] ? 'popular' : ''; ?>" 
                                     onclick="selectPackage(<?php echo $package['amount'] + $package['bonus']; ?>, <?php echo $package['price']; ?>)">
                                    
                                    <?php if ($package['popular']): ?>
                                        <div class="popular-badge">
                                            <i class="fas fa-star me-1"></i>POPÜLER
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
                                                <i class="fas fa-piggy-bank me-1"></i>
                                                <?php echo number_format(($package['savings'] / $package['price']) * 100, 0); ?>% tasarruf
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="package-button">
                                            <span class="btn-text">
                                                <i class="fas fa-shopping-cart me-1"></i>Satın Al
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Özel Tutar -->
                    <div class="custom-amount-section">
                        <div class="section-header">
                            <h4 class="mb-2">
                                <i class="fas fa-edit me-2 text-info"></i>Özel Tutar
                            </h4>
                            <p class="text-muted">İstediğiniz tutarda kredi yükleyin</p>
                        </div>
                        
                        <div class="custom-amount-card">
                            <form method="POST" id="creditForm" class="credit-form">
                                <input type="hidden" name="add_credits" value="1">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="amount" class="form-label">
                                            <i class="fas fa-money-bill-wave me-1"></i>Kredi Tutarı
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
                                            <i class="fas fa-credit-card me-1"></i>Ödeme Yöntemi
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
                                        <i class="fas fa-credit-card me-2"></i>
                                        Kredi Yükle
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Son İşlemler -->
                    <div class="recent-transactions-section">
                        <div class="section-header">
                            <h4 class="mb-2">
                                <i class="fas fa-clock me-2 text-secondary"></i>Son İşlemler
                            </h4>
                            <a href="transactions.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list me-1"></i>Tümünü Gör
                            </a>
                        </div>
                        
                        <?php if (empty($recentTransactions)): ?>
                            <div class="empty-transactions">
                                <div class="empty-icon">
                                    <i class="fas fa-receipt"></i>
                                </div>
                                <h6>Henüz işlem bulunmuyor</h6>
                                <p class="text-muted">İlk kredi yüklemenizi yaparak başlayabilirsiniz</p>
                            </div>
                        <?php else: ?>
                            <div class="transactions-list">
                                <?php foreach (array_slice($recentTransactions, 0, 5) as $transaction): ?>
                                    <div class="transaction-item">
                                        <div class="transaction-icon">
                                            <?php
                                            $iconClass = 'fas fa-circle';
                                            $iconColor = 'secondary';
                                            
                                            $transactionType = $transaction['transaction_type'] ?? '';
                                            switch ($transactionType) {
                                                case 'add':
                                                    $iconClass = 'fas fa-plus-circle';
                                                    $iconColor = 'success';
                                                    break;
                                                case 'deduct':
                                                    $iconClass = 'fas fa-minus-circle';
                                                    $iconColor = 'danger';
                                                    break;
                                            }
                                            ?>
                                            <i class="<?php echo $iconClass; ?> text-<?php echo $iconColor; ?>"></i>
                                        </div>
                                        
                                        <div class="transaction-details">
                                            <div class="transaction-title">
                                                <?php
                                                $title = 'Bilinmeyen İşlem';
                                                $transactionType = $transaction['transaction_type'] ?? '';
                                                switch ($transactionType) {
                                                    case 'add':
                                                        $title = 'Kredi Yükleme';
                                                        break;
                                                    case 'deduct':
                                                        $title = 'Kredi Kullanımı';
                                                        break;
                                                }
                                                echo $title;
                                                ?>
                                            </div>
                                            <div class="transaction-desc">
                                                <?php echo htmlspecialchars($transaction['description']); ?>
                                            </div>
                                            <div class="transaction-date">
                                                <?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="transaction-amount">
                                            <span class="amount text-<?php echo ($transaction['transaction_type'] ?? '') === 'add' ? 'success' : 'danger'; ?>">
                                                <?php echo ($transaction['transaction_type'] ?? '') === 'add' ? '+' : '-'; ?>
                                                <?php echo number_format($transaction['amount'], 2); ?> TL
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sağ Kolon - Bilgilendirme -->
                <div class="col-lg-4">
                    <!-- Ücret Tarifesi -->
                    <div class="info-card mb-4">
                        <div class="info-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list-ul me-2"></i>Ücret Tarifesi
                            </h5>
                        </div>
                        <div class="info-body">
                            <div class="price-list">
                                <div class="price-item">
                                    <div class="service-info">
                                        <i class="fas fa-microchip text-primary"></i>
                                        <span>ECU Tuning</span>
                                    </div>
                                    <div class="service-price">5 TL</div>
                                </div>
                                
                                <div class="price-item">
                                    <div class="service-info">
                                        <i class="fas fa-cogs text-success"></i>
                                        <span>TCU Tuning</span>
                                    </div>
                                    <div class="service-price">7 TL</div>
                                </div>
                                
                                <div class="price-item">
                                    <div class="service-info">
                                        <i class="fas fa-key text-warning"></i>
                                        <span>İmmobilizer</span>
                                    </div>
                                    <div class="service-price">3 TL</div>
                                </div>
                                
                                <div class="price-item">
                                    <div class="service-info">
                                        <i class="fas fa-wrench text-info"></i>
                                        <span>DPF/EGR Off</span>
                                    </div>
                                    <div class="service-price">4 TL</div>
                                </div>
                                
                                <div class="price-item">
                                    <div class="service-info">
                                        <i class="fas fa-edit text-danger"></i>
                                        <span>Revize İşlemi</span>
                                    </div>
                                    <div class="service-price">2 TL</div>
                                </div>
                            </div>
                            
                            <div class="price-note">
                                <i class="fas fa-info-circle me-1"></i>
                                <small>Fiyatlar dosya karmaşıklığına göre değişebilir</small>
                            </div>
                        </div>
                    </div>

                    <!-- Güvenlik Bilgileri -->
                    <div class="info-card mb-4">
                        <div class="info-header">
                            <h5 class="mb-0">
                                <i class="fas fa-shield-alt me-2"></i>Ödeme Güvenliği
                            </h5>
                        </div>
                        <div class="info-body">
                            <div class="security-features">
                                <div class="security-item">
                                    <i class="fas fa-lock text-success"></i>
                                    <span>256-bit SSL Şifreleme</span>
                                </div>
                                <div class="security-item">
                                    <i class="fas fa-shield-check text-success"></i>
                                    <span>3D Secure Doğrulama</span>
                                </div>
                                <div class="security-item">
                                    <i class="fas fa-certificate text-success"></i>
                                    <span>PCI DSS Sertifikalı</span>
                                </div>
                                <div class="security-item">
                                    <i class="fas fa-user-shield text-success"></i>
                                    <span>Kişisel Veri Koruması</span>
                                </div>
                            </div>
                            
                            <div class="security-badge">
                                <i class="fas fa-award fa-2x text-success mb-2"></i>
                                <div class="fw-bold">%100 Güvenli</div>
                                <small class="text-muted">Ödemeleriniz tamamen güvende</small>
                            </div>
                        </div>
                    </div>

                    <!-- Destek & İletişim -->
                    <div class="info-card">
                        <div class="info-header">
                            <h5 class="mb-0">
                                <i class="fas fa-headset me-2"></i>Destek & Yardım
                            </h5>
                        </div>
                        <div class="info-body">
                            <p class="text-muted mb-3">Kredi yükleme konusunda yardıma mı ihtiyacınız var?</p>
                            
                            <div class="support-options">
                                <a href="mailto:<?php echo SITE_EMAIL; ?>" class="support-option">
                                    <i class="fas fa-envelope"></i>
                                    <span>E-posta Gönder</span>
                                </a>
                                
                                <a href="tel:+905551234567" class="support-option">
                                    <i class="fas fa-phone"></i>
                                    <span>Telefon Desteği</span>
                                </a>
                                
                                <button class="support-option" onclick="openWhatsApp()">
                                    <i class="fab fa-whatsapp"></i>
                                    <span>WhatsApp</span>
                                </button>
                                
                                <a href="#" class="support-option" onclick="openLiveChat()">
                                    <i class="fas fa-comments"></i>
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
/* Modern Credits Page Styles */
.credit-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 2rem;
    color: white;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
}

.credit-content {
    display: flex;
    justify-content: between;
    align-items: center;
}

.credit-info {
    flex: 1;
}

.credit-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    opacity: 0.9;
}

.credit-amount {
    display: flex;
    align-items: baseline;
    margin-bottom: 1.5rem;
}

.amount {
    font-size: 3.5rem;
    font-weight: 700;
    line-height: 1;
}

.currency {
    font-size: 1.5rem;
    font-weight: 500;
    margin-left: 0.5rem;
    opacity: 0.8;
}

.credit-stats {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    opacity: 0.9;
}

.stat-item i {
    margin-right: 0.5rem;
}

.credit-visual {
    margin-left: 2rem;
}

.credit-icon {
    width: 120px;
    height: 120px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    opacity: 0.3;
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

/* Responsive */
@media (max-width: 767.98px) {
    .credit-content {
        flex-direction: column;
        text-align: center;
    }
    
    .credit-visual {
        margin-left: 0;
        margin-top: 2rem;
    }
    
    .credit-stats {
        flex-direction: column;
        gap: 1rem;
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

// Toast Notification Function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
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
</script>

<?php
// Footer include
include '../includes/user_footer.php';
?>