<?php
/**
 * Mr ECU - Kullanıcı Kredi Yönetimi
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

// Kredi işlemlerini getir
try {
    $stmt = $pdo->prepare("
        SELECT ct.*, u.username as admin_username 
        FROM credit_transactions ct
        LEFT JOIN users u ON ct.admin_id = u.id
        WHERE ct.user_id = ?
        ORDER BY ct.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$userId]);
    $creditTransactions = $stmt->fetchAll();
} catch(PDOException $e) {
    $creditTransactions = [];
}

// Kullanıcının mevcut kredi bakiyesi
$userCredits = $user->getUserCredits($userId);

// İstatistikler
$totalDeposit = 0;
$totalWithdraw = 0;
$totalFileCharges = 0;

foreach ($creditTransactions as $transaction) {
    switch ($transaction['type']) {
        case 'deposit':
            $totalDeposit += $transaction['amount'];
            break;
        case 'withdraw':
        case 'file_charge':
            if ($transaction['type'] === 'file_charge') {
                $totalFileCharges += $transaction['amount'];
            }
            $totalWithdraw += $transaction['amount'];
            break;
    }
}

$pageTitle = 'Kredi İşlemleri';
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
                        <i class="fas fa-coins me-2"></i>Kredi İşlemleri
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#buyCreditsModal">
                                <i class="fas fa-plus me-1"></i>Kredi Satın Al
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Kredi Özeti -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card primary">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo number_format($userCredits, 2); ?></h4>
                                    <p class="text-muted mb-0">Mevcut Bakiye</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-wallet text-primary" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card success">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo number_format($totalDeposit, 2); ?></h4>
                                    <p class="text-muted mb-0">Toplam Yüklenen</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-up text-success" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card danger">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo number_format($totalWithdraw, 2); ?></h4>
                                    <p class="text-muted mb-0">Toplam Harcanan</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-arrow-down text-danger" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6">
                        <div class="dashboard-card warning">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-1"><?php echo number_format($totalFileCharges, 2); ?></h4>
                                    <p class="text-muted mb-0">Dosya İndirme</p>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-download text-warning" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kredi Paketleri -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-shopping-cart me-2"></i>Kredi Paketleri
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <div class="card border-primary h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-coins text-primary mb-3" style="font-size: 2rem;"></i>
                                                <h4>10 Kredi</h4>
                                                <h3 class="text-primary">₺50</h3>
                                                <p class="text-muted">Küçük projeler için</p>
                                                <button class="btn btn-outline-primary" onclick="selectCreditPackage(10, 50)">
                                                    Satın Al
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <div class="card border-success h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-coins text-success mb-3" style="font-size: 2rem;"></i>
                                                <h4>25 Kredi</h4>
                                                <h3 class="text-success">₺100</h3>
                                                <p class="text-muted">Popüler seçim</p>
                                                <button class="btn btn-success" onclick="selectCreditPackage(25, 100)">
                                                    Satın Al
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <div class="card border-warning h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-coins text-warning mb-3" style="font-size: 2rem;"></i>
                                                <h4>50 Kredi</h4>
                                                <h3 class="text-warning">₺175</h3>
                                                <p class="text-muted">%12 indirim</p>
                                                <button class="btn btn-outline-warning" onclick="selectCreditPackage(50, 175)">
                                                    Satın Al
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <div class="card border-danger h-100">
                                            <div class="card-body text-center">
                                                <i class="fas fa-coins text-danger mb-3" style="font-size: 2rem;"></i>
                                                <h4>100 Kredi</h4>
                                                <h3 class="text-danger">₺300</h3>
                                                <p class="text-muted">%25 indirim</p>
                                                <button class="btn btn-outline-danger" onclick="selectCreditPackage(100, 300)">
                                                    Satın Al
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- İşlem Geçmişi -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>İşlem Geçmişi
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($creditTransactions)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-receipt text-muted" style="font-size: 4rem;"></i>
                                <h4 class="mt-3 text-muted">Henüz işlem yok</h4>
                                <p class="text-muted">İlk kredi yüklemenizi yaparak başlayın.</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#buyCreditsModal">
                                    <i class="fas fa-plus me-1"></i>Kredi Satın Al
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tarih</th>
                                            <th>İşlem</th>
                                            <th>Miktar</th>
                                            <th>Açıklama</th>
                                            <th>Admin</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($creditTransactions as $transaction): ?>
                                            <tr>
                                                <td><?php echo formatDate($transaction['created_at']); ?></td>
                                                <td>
                                                    <?php
                                                    $iconClass = '';
                                                    $textClass = '';
                                                    $typeText = '';
                                                    
                                                    switch ($transaction['type']) {
                                                        case 'deposit':
                                                            $iconClass = 'fas fa-plus-circle text-success';
                                                            $textClass = 'text-success';
                                                            $typeText = 'Kredi Yükleme';
                                                            break;
                                                        case 'withdraw':
                                                            $iconClass = 'fas fa-minus-circle text-danger';
                                                            $textClass = 'text-danger';
                                                            $typeText = 'Kredi Çekme';
                                                            break;
                                                        case 'file_charge':
                                                            $iconClass = 'fas fa-download text-warning';
                                                            $textClass = 'text-warning';
                                                            $typeText = 'Dosya İndirme';
                                                            break;
                                                        case 'refund':
                                                            $iconClass = 'fas fa-undo text-info';
                                                            $textClass = 'text-info';
                                                            $typeText = 'İade';
                                                            break;
                                                    }
                                                    ?>
                                                    <i class="<?php echo $iconClass; ?> me-2"></i>
                                                    <span class="<?php echo $textClass; ?>"><?php echo $typeText; ?></span>
                                                </td>
                                                <td>
                                                    <strong class="<?php echo $textClass; ?>">
                                                        <?php if ($transaction['type'] === 'deposit' || $transaction['type'] === 'refund'): ?>
                                                            +<?php echo $transaction['amount']; ?>
                                                        <?php else: ?>
                                                            -<?php echo $transaction['amount']; ?>
                                                        <?php endif; ?>
                                                    </strong>
                                                </td>
                                                <td>
                                                    <?php if ($transaction['description']): ?>
                                                        <span class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($transaction['description']); ?>">
                                                            <?php echo htmlspecialchars($transaction['description']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($transaction['admin_username']): ?>
                                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($transaction['admin_username']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Sistem</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">Tamamlandı</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Kredi Satın Alma Modal -->
    <div class="modal fade" id="buyCreditsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shopping-cart me-2"></i>Kredi Satın Al
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Bilgi:</strong> Kredi satın alma işlemi şu anda test modundadır. 
                        Gerçek ödeme işlemi yapılmayacaktır.
                    </div>
                    
                    <form id="creditPurchaseForm">
                        <div class="mb-3">
                            <label for="creditAmount" class="form-label">Kredi Miktarı</label>
                            <input type="number" class="form-control" id="creditAmount" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="totalPrice" class="form-label">Toplam Tutar</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="totalPrice" readonly>
                                <span class="input-group-text">₺</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="paymentMethod" class="form-label">Ödeme Yöntemi</label>
                            <select class="form-select" id="paymentMethod">
                                <option value="credit_card">Kredi Kartı</option>
                                <option value="bank_transfer">Banka Havalesi</option>
                                <option value="mobile_payment">Mobil Ödeme</option>
                            </select>
                        </div>
                        
                        <!-- Kredi Kartı Bilgileri -->
                        <div id="creditCardForm">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="cardNumber" class="form-label">Kart Numarası</label>
                                    <input type="text" class="form-control" id="cardNumber" placeholder="**** **** **** ****">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label for="expiryDate" class="form-label">Son Kullanma</label>
                                    <input type="text" class="form-control" id="expiryDate" placeholder="MM/YY">
                                </div>
                                <div class="col-6 mb-3">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="cvv" placeholder="***">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="cardName" class="form-label">Kart Üzerindeki İsim</label>
                                <input type="text" class="form-control" id="cardName" placeholder="AD SOYAD">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" onclick="processCreditPurchase()">
                        <i class="fas fa-credit-card me-1"></i>Ödemeyi Tamamla
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        let selectedCredits = 0;
        let selectedPrice = 0;

        function selectCreditPackage(credits, price) {
            selectedCredits = credits;
            selectedPrice = price;
            
            document.getElementById('creditAmount').value = credits;
            document.getElementById('totalPrice').value = price;
            
            // Modal'ı aç
            const modal = new bootstrap.Modal(document.getElementById('buyCreditsModal'));
            modal.show();
        }

        function processCreditPurchase() {
            // Test için basit bir simülasyon
            if (selectedCredits === 0) {
                alert('Lütfen bir kredi paketi seçin.');
                return;
            }

            // Loading durumu
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>İşleniyor...';
            button.disabled = true;

            // Simüle edilmiş ödeme işlemi
            setTimeout(function() {
                alert(`${selectedCredits} kredi başarıyla hesabınıza yüklendi! (Test Modu)`);
                
                // Modal'ı kapat
                const modal = bootstrap.Modal.getInstance(document.getElementById('buyCreditsModal'));
                modal.hide();
                
                // Sayfayı yenile
                location.reload();
            }, 2000);
        }

        // Ödeme yöntemi değişikliği
        document.getElementById('paymentMethod').addEventListener('change', function() {
            const creditCardForm = document.getElementById('creditCardForm');
            
            if (this.value === 'credit_card') {
                creditCardForm.style.display = 'block';
            } else {
                creditCardForm.style.display = 'none';
            }
        });

        // Kart numarası formatlaması
        document.getElementById('cardNumber').addEventListener('input', function() {
            let value = this.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            this.value = formattedValue;
        });

        // Son kullanma tarihi formatlaması
        document.getElementById('expiryDate').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            this.value = value;
        });

        // CVV formatlaması
        document.getElementById('cvv').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').substring(0, 3);
        });
    </script>
</body>
</html>
