<?php
/**
 * Mr ECU - Kullanıcı Kredi Yönetimi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü otomatik yapılır
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
        // Şimdilik simülasyon yapıyoruz
        try {
            $stmt = $pdo->prepare("
                INSERT INTO credit_transactions (user_id, type, amount, description, created_at) 
                VALUES (?, 'deposit', ?, ?, NOW())
            ");
            $stmt->execute([$userId, $amount, "Kredi yükleme - $paymentMethod"]);
            
            // Kullanıcının kredisini güncelle
            $stmt = $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
            $stmt->execute([$amount, $userId]);
            
            // Session'ı güncelle
            $_SESSION['credits'] = $user->getUserCredits($userId);
            
            $success = number_format($amount, 2) . " TL kredi başarıyla hesabınıza yüklendi.";
            
            // Log kaydı
            $user->logAction($userId, 'credit_deposit', "Kredi yükleme: $amount TL");
            
        } catch(PDOException $e) {
            $error = 'Kredi yükleme sırasında hata oluştu.';
        }
    }
}

// Kredi işlemlerini getir (sayfalama ile)
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    // Toplam işlem sayısı
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM credit_transactions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalTransactions = $stmt->fetchColumn();
    
    // İşlemleri getir
    $stmt = $pdo->prepare("
        SELECT ct.*, u.username as admin_username 
        FROM credit_transactions ct
        LEFT JOIN users u ON ct.admin_id = u.id
        WHERE ct.user_id = ?
        ORDER BY ct.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$userId, $limit, $offset]);
    $creditTransactions = $stmt->fetchAll();
    
    $totalPages = ceil($totalTransactions / $limit);
} catch(PDOException $e) {
    $creditTransactions = [];
    $totalTransactions = 0;
    $totalPages = 0;
}

// İstatistikler
$totalDeposit = 0;
$totalSpent = 0;

try {
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposit,
            SUM(CASE WHEN type IN ('withdraw', 'file_charge') THEN amount ELSE 0 END) as total_spent
        FROM credit_transactions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    $totalDeposit = $stats['total_deposit'] ?? 0;
    $totalSpent = $stats['total_spent'] ?? 0;
} catch(PDOException $e) {
    $totalDeposit = 0;
    $totalSpent = 0;
}

// Kredi paketleri
$creditPackages = [
    ['amount' => 25, 'bonus' => 0, 'price' => 25, 'popular' => false],
    ['amount' => 50, 'bonus' => 5, 'price' => 50, 'popular' => false],
    ['amount' => 100, 'bonus' => 15, 'price' => 100, 'popular' => true],
    ['amount' => 200, 'bonus' => 40, 'price' => 200, 'popular' => false],
    ['amount' => 500, 'bonus' => 125, 'price' => 500, 'popular' => false]
];

// Sayfa bilgileri
$pageTitle = 'Kredi Yükle';
$pageDescription = 'Hesabınıza kredi yükleyin ve işlem geçmişinizi görüntüleyin.';

// Header ve Sidebar include
include '../includes/user_header.php';
include '../includes/user_sidebar.php';
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

<div class="row">
    <!-- Sol Kolon - Kredi Yükleme -->
    <div class="col-lg-8">
        <!-- Mevcut Kredi Durumu -->
        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <div class="card-body text-white p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4 class="mb-2">
                            <i class="fas fa-coins me-2"></i>
                            Mevcut Kredi Bakiyeniz
                        </h4>
                        <h2 class="display-4 fw-bold mb-2"><?php echo number_format($_SESSION['credits'], 2); ?> TL</h2>
                        <p class="mb-0 opacity-75">
                            Toplam yüklediğiniz: <strong><?php echo number_format($totalDeposit, 2); ?> TL</strong> | 
                            Toplam harcanan: <strong><?php echo number_format($totalSpent, 2); ?> TL</strong>
                        </p>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="fas fa-wallet" style="font-size: 6rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kredi Paketleri -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-credit-card me-2"></i>
                    Kredi Paketleri
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <?php foreach ($creditPackages as $package): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card credit-package <?php echo $package['popular'] ? 'border-warning' : 'border-light'; ?> h-100">
                                <?php if ($package['popular']): ?>
                                    <div class="card-header bg-warning text-dark text-center">
                                        <small class="fw-bold">
                                            <i class="fas fa-star me-1"></i>POPÜLER
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body text-center">
                                    <h4 class="text-primary mb-2"><?php echo $package['amount']; ?> TL</h4>
                                    
                                    <?php if ($package['bonus'] > 0): ?>
                                        <div class="badge bg-success mb-2">
                                            +<?php echo $package['bonus']; ?> TL Bonus
                                        </div>
                                        <p class="text-muted mb-2">
                                            Toplam: <strong><?php echo $package['amount'] + $package['bonus']; ?> TL</strong>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <h5 class="text-dark mb-3"><?php echo number_format($package['price'], 2); ?> TL</h5>
                                    
                                    <button type="button" class="btn btn-<?php echo $package['popular'] ? 'warning' : 'outline-primary'; ?> btn-sm w-100" 
                                            onclick="selectPackage(<?php echo $package['amount'] + $package['bonus']; ?>, <?php echo $package['price']; ?>)">
                                        <i class="fas fa-shopping-cart me-1"></i>
                                        Satın Al
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Özel Tutar -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-info">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-edit me-2"></i>Özel Tutar
                                </h6>
                                <form method="POST" id="creditForm" class="row g-3" data-loading="true">
                                    <input type="hidden" name="add_credits" value="1">
                                    
                                    <div class="col-md-4">
                                        <label for="amount" class="form-label">Tutar (TL)</label>
                                        <input type="number" class="form-control" id="amount" name="amount" 
                                               min="10" max="1000" step="0.01" placeholder="Örn: 75" required>
                                        <div class="form-text">Min: 10 TL, Max: 1000 TL</div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="payment_method" class="form-label">Ödeme Yöntemi</label>
                                        <select class="form-select" id="payment_method" name="payment_method" required>
                                            <option value="">Seçiniz...</option>
                                            <option value="credit_card">Kredi Kartı</option>
                                            <option value="debit_card">Banka Kartı</option>
                                            <option value="bank_transfer">Havale/EFT</option>
                                            <option value="paypal">PayPal</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-success w-100" data-original-text="Kredi Yükle">
                                            <i class="fas fa-plus me-2"></i>Kredi Yükle
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- İşlem Geçmişi -->
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    İşlem Geçmişi (<?php echo $totalTransactions; ?> işlem)
                </h5>
                
                <?php if (!empty($creditTransactions)): ?>
                    <button class="btn btn-outline-secondary btn-sm" onclick="exportTransactions()">
                        <i class="fas fa-download me-1"></i>Excel İndir
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="card-body p-0">
                <?php if (empty($creditTransactions)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Henüz kredi işlemi bulunmuyor</h6>
                        <p class="text-muted mb-3">İlk kredi yüklemenizi yaparak başlayabilirsiniz</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tarih</th>
                                    <th>İşlem Türü</th>
                                    <th>Açıklama</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($creditTransactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo date('d.m.Y', strtotime($transaction['created_at'])); ?></strong><br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($transaction['created_at'])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $typeClass = [
                                                'deposit' => 'success',
                                                'withdraw' => 'warning', 
                                                'file_charge' => 'info'
                                            ];
                                            $typeText = [
                                                'deposit' => 'Kredi Yükleme',
                                                'withdraw' => 'Para Çekme',
                                                'file_charge' => 'Dosya İşlemi'
                                            ];
                                            $typeIcon = [
                                                'deposit' => 'plus-circle',
                                                'withdraw' => 'minus-circle',
                                                'file_charge' => 'file-alt'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $typeClass[$transaction['type']] ?? 'secondary'; ?>">
                                                <i class="fas fa-<?php echo $typeIcon[$transaction['type']] ?? 'question'; ?> me-1"></i>
                                                <?php echo $typeText[$transaction['type']] ?? 'Bilinmiyor'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-block" style="max-width: 200px;" 
                                                  title="<?php echo htmlspecialchars($transaction['description']); ?>">
                                                <?php echo htmlspecialchars($transaction['description']); ?>
                                            </span>
                                            <?php if ($transaction['admin_username']): ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-user-shield"></i> 
                                                    <?php echo htmlspecialchars($transaction['admin_username']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-<?php echo $transaction['type'] === 'deposit' ? 'success' : 'danger'; ?>">
                                                <?php echo $transaction['type'] === 'deposit' ? '+' : '-'; ?>
                                                <?php echo number_format($transaction['amount'], 2); ?> TL
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Tamamlandı
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="card-footer">
                            <nav aria-label="İşlem geçmişi sayfalama">
                                <ul class="pagination pagination-sm justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $start = max(1, $page - 2);
                                    $end = min($totalPages, $page + 2);
                                    ?>
                                    
                                    <?php for ($i = $start; $i <= $end; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">
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
    </div>
    
    <!-- Sağ Kolon - Bilgilendirme -->
    <div class="col-lg-4">
        <!-- Kredi Bilgileri -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Kredi Kullanımı
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6>Dosya İşleme Ücretleri:</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-microchip text-primary me-2"></i>
                            ECU Tuning: <strong>5 TL</strong>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-cogs text-success me-2"></i>
                            TCU Tuning: <strong>7 TL</strong>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-key text-warning me-2"></i>
                            İmmobilizer: <strong>3 TL</strong>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-wrench text-info me-2"></i>
                            DPF/EGR Off: <strong>4 TL</strong>
                        </li>
                    </ul>
                </div>
                
                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-lightbulb me-1"></i>
                        <strong>İpucu:</strong> Büyük paketlerde bonus kredi kazanırsınız!
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Ödeme Güvenliği -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">
                    <i class="fas fa-shield-alt me-2"></i>
                    Ödeme Güvenliği
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="fas fa-lock fa-3x text-success mb-2"></i>
                    <h6>%100 Güvenli</h6>
                </div>
                
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        SSL Şifreleme
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        3D Secure Doğrulama
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        PCI DSS Uyumlu
                    </li>
                    <li class="mb-0">
                        <i class="fas fa-check text-success me-2"></i>
                        256-bit Şifreleme
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Destek -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0">
                    <i class="fas fa-headset me-2"></i>
                    Destek
                </h6>
            </div>
            <div class="card-body">
                <p class="mb-3">Kredi yükleme konusunda yardıma mı ihtiyacınız var?</p>
                
                <div class="d-grid gap-2">
                    <a href="mailto:<?php echo SITE_EMAIL; ?>" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-envelope me-2"></i>E-posta Gönder
                    </a>
                    
                    <a href="tel:+905551234567" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-phone me-2"></i>Telefon: +90 555 123 45 67
                    </a>
                    
                    <button class="btn btn-outline-info btn-sm" onclick="openWhatsApp()">
                        <i class="fab fa-whatsapp me-2"></i>WhatsApp Destek
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Sayfa özel JavaScript
$pageJS = "
// Paket seçimi
function selectPackage(amount, price) {
    document.getElementById('amount').value = amount;
    document.getElementById('amount').focus();
    
    // Paket seçildiğinde ödeme yöntemini otomatik kredi kartı yap
    document.getElementById('payment_method').value = 'credit_card';
    
    showToast(amount + ' TL\'lik paket seçildi. Ödeme yöntemini seçin.', 'info');
}

// WhatsApp desteği
function openWhatsApp() {
    const message = 'Merhaba, kredi yükleme konusunda yardıma ihtiyacım var.';
    const phoneNumber = '905551234567';
    const url = 'https://wa.me/' + phoneNumber + '?text=' + encodeURIComponent(message);
    window.open(url, '_blank');
}

// İşlem geçmişi export
function exportTransactions() {
    window.open('export-transactions.php?format=excel', '_blank');
}

// Form validasyonu
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
    
    // Onay modalı
    if (!confirm('Hesabınıza ' + amount.toFixed(2) + ' TL kredi yüklemek istediğinizden emin misiniz?')) {
        e.preventDefault();
        return false;
    }
});

// Tutar formatı
document.getElementById('amount').addEventListener('input', function() {
    const value = parseFloat(this.value);
    if (value && value >= 10) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    } else {
        this.classList.remove('is-valid');
        if (this.value) {
            this.classList.add('is-invalid');
        }
    }
});

// Kredi paketlerini hover efekti
document.querySelectorAll('.credit-package').forEach(function(card) {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.transition = 'all 0.3s ease';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});
";

// Footer include
include '../includes/user_footer.php';
?>
