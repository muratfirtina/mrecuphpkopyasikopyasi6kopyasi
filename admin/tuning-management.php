<?php
/**
 * Tuning Verileri Admin Panel
 * Mr ECU Projesi - Minimal Başlangıç
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/TuningModel.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

$tuning = new TuningModel($pdo);
$message = '';
$messageType = '';

// POST işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_price'])) {
        $stageId = sanitize($_POST['stage_id']);
        $price = (float)$_POST['price'];
        
        // GUID format kontrolü
        if (!isValidUUID($stageId)) {
            $message = 'Geçersiz stage ID formatı!';
            $messageType = 'error';
        } elseif ($tuning->updateStagePrice($stageId, $price)) {
            $message = 'Fiyat başarıyla güncellendi!';
            $messageType = 'success';
        } else {
            $message = 'Fiyat güncellenirken hata oluştu!';
            $messageType = 'error';
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        $stageId = sanitize($_POST['stage_id']);
        
        // GUID format kontrolü
        if (!isValidUUID($stageId)) {
            $message = 'Geçersiz stage ID formatı!';
            $messageType = 'error';
        } elseif ($tuning->toggleStageStatus($stageId)) {
            $message = 'Durum başarıyla değiştirildi!';
            $messageType = 'success';
        } else {
            $message = 'Durum değiştirilirken hata oluştu!';
            $messageType = 'error';
        }
    }
}

// Verileri getir
try {
    $brandStats = $tuning->getBrandStats();
    $fuelStats = $tuning->getFuelTypeStats();
    $latestStages = $tuning->getLatestStages(10);
    $popularEngines = $tuning->getPopularEngines(10);
} catch (Exception $e) {
    $message = 'Veri getirilirken hata: ' . $e->getMessage();
    $messageType = 'error';
    $brandStats = [];
    $fuelStats = [];
    $latestStages = [];
    $popularEngines = [];
}

include __DIR__ . '/../includes/admin_header.php';
?>

<style>
    .admin-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #3498db;
        display: block;
    }

    .stat-label {
        color: #7f8c8d;
        margin-top: 5px;
    }

    .section {
        background: white;
        border-radius: 10px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .section h2 {
        color: #2c3e50;
        margin-bottom: 20px;
        border-bottom: 2px solid #3498db;
        padding-bottom: 10px;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .data-table th {
        background: #3498db;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: 600;
    }

    .data-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #e1e8ed;
    }

    .data-table tr:hover {
        background: #f8f9fa;
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
        display: inline-block;
        margin: 2px;
    }

    .btn-primary { background: #3498db; color: white; }
    .btn-success { background: #27ae60; color: white; }
    .btn-warning { background: #f39c12; color: white; }
    .btn-danger { background: #e74c3c; color: white; }
    .btn-info { background: #17a2b8; color: white; }

    .btn:hover {
        opacity: 0.8;
    }

    .message {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .price-form {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .price-input {
        width: 80px;
        padding: 4px 8px;
        border: 1px solid #ddd;
        border-radius: 3px;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }

    .status-active {
        background: #d4edda;
        color: #155724;
    }

    .status-inactive {
        background: #f8d7da;
        color: #721c24;
    }

    .tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .tab {
        padding: 10px 20px;
        border: none;
        background: #ecf0f1;
        color: #7f8c8d;
        border-radius: 5px;
        cursor: pointer;
    }

    .tab.active {
        background: #3498db;
        color: white;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }
</style>

<div class="admin-container">
    <h1>🚗 Tuning Verileri Yönetimi</h1>

    <?php if ($message): ?>
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- İstatistikler -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-number"><?= count($brandStats) ?></span>
            <div class="stat-label">Toplam Marka</div>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?= array_sum(array_column($brandStats, 'model_count')) ?></span>
            <div class="stat-label">Toplam Model</div>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?= array_sum(array_column($brandStats, 'engine_count')) ?></span>
            <div class="stat-label">Toplam Motor</div>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?= array_sum(array_column($brandStats, 'total_stages')) ?></span>
            <div class="stat-label">Toplam Stage</div>
        </div>
    </div>

    <!-- Sekmeler -->
    <div class="tabs">
        <button class="tab active" onclick="showTab('brands')">📊 Marka İstatistikleri</button>
        <button class="tab" onclick="showTab('fuel')">⛽ Yakıt Tipleri</button>
        <button class="tab" onclick="showTab('latest')">🆕 Son Eklenenler</button>
        <button class="tab" onclick="showTab('popular')">🔥 Popüler Motorlar</button>
    </div>

    <!-- Marka İstatistikleri -->
    <div id="brands" class="tab-content active">
        <div class="section">
            <h2>📊 Marka Bazında İstatistikler</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Marka</th>
                        <th>Model Sayısı</th>
                        <th>Motor Sayısı</th>
                        <th>Toplam Stage</th>
                        <th>Ort. Güç Artışı</th>
                        <th>Max. Güç Artışı</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($brandStats as $brand): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($brand['brand_name']) ?></strong></td>
                        <td><?= $brand['model_count'] ?></td>
                        <td><?= $brand['engine_count'] ?></td>
                        <td><?= $brand['total_stages'] ?></td>
                        <td><?= round($brand['avg_power_gain'], 1) ?> HP</td>
                        <td><?= $brand['max_power_gain'] ?> HP</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Yakıt Tipi İstatistikleri -->
    <div id="fuel" class="tab-content">
        <div class="section">
            <h2>⛽ Yakıt Tipi İstatistikleri</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Yakıt Tipi</th>
                        <th>Motor Sayısı</th>
                        <th>Ort. Orijinal Güç</th>
                        <th>Ort. Tuning Güç</th>
                        <th>Ort. Güç Artışı</th>
                        <th>Max. Güç Artışı</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fuelStats as $fuel): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($fuel['fuel_type']) ?></strong></td>
                        <td><?= $fuel['total_engines'] ?></td>
                        <td><?= round($fuel['avg_original_power'], 1) ?> HP</td>
                        <td><?= round($fuel['avg_tuning_power'], 1) ?> HP</td>
                        <td><?= round($fuel['avg_power_gain'], 1) ?> HP</td>
                        <td><?= $fuel['max_power_gain'] ?> HP</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Son Eklenenler -->
    <div id="latest" class="tab-content">
        <div class="section">
            <h2>🆕 Son Eklenen Stage'ler</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Araç</th>
                        <th>Motor</th>
                        <th>Stage</th>
                        <th>Güç</th>
                        <th>Artış</th>
                        <th>Fiyat</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $latest = $tuning->searchDetailed(['limit' => 20]);
                    foreach ($latest as $stage):
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($stage['brand_name'] . ' ' . $stage['model_name']) ?></strong><br>
                            <small><?= htmlspecialchars($stage['year_range']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($stage['engine_name']) ?></td>
                        <td><?= htmlspecialchars($stage['stage_name']) ?></td>
                        <td><?= $stage['original_power'] ?> → <?= $stage['tuning_power'] ?> HP</td>
                        <td style="color: #27ae60;">+<?= $stage['difference_power'] ?> HP</td>
                        <td>
                            <form method="post" class="price-form">
                                <input type="hidden" name="stage_id" value="<?= $stage['stage_id'] ?>">
                                <input type="number" name="price" class="price-input" value="<?= $stage['price'] ?>" step="0.01">
                                <button type="submit" name="update_price" class="btn btn-warning">💰</button>
                            </form>
                        </td>
                        <td>
                            <span class="status-badge <?= $stage['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $stage['is_active'] ? 'Aktif' : 'Pasif' ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="stage_id" value="<?= $stage['stage_id'] ?>">
                                <button type="submit" name="toggle_status" class="btn <?= $stage['is_active'] ? 'btn-danger' : 'btn-success' ?>">
                                    <?= $stage['is_active'] ? '❌' : '✅' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Popüler Motorlar -->
    <div id="popular" class="tab-content">
        <div class="section">
            <h2>🔥 Popüler Motorlar</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Motor</th>
                        <th>Marka/Model</th>
                        <th>Yakıt</th>
                        <th>Stage Sayısı</th>
                        <th>Ort. Güç Artışı</th>
                        <th>Max. Tuning Gücü</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($popularEngines as $engine): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($engine['name']) ?></strong></td>
                        <td><?= htmlspecialchars($engine['brand_name'] . ' ' . $engine['model_name']) ?><br>
                            <small><?= htmlspecialchars($engine['year_range']) ?></small></td>
                        <td><?= htmlspecialchars($engine['fuel_type']) ?></td>
                        <td><?= $engine['stage_count'] ?></td>
                        <td><?= round($engine['avg_power_gain'], 1) ?> HP</td>
                        <td><?= $engine['max_tuning_power'] ?> HP</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function showTab(tabName) {
    // Tüm sekmeleri gizle
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Tüm sekme butonlarından active sınıfını kaldır
    const tabButtons = document.querySelectorAll('.tab');
    tabButtons.forEach(button => button.classList.remove('active'));
    
    // Seçilen sekmeyi göster
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}

// Fiyat güncelleme formlarında Enter tuşu desteği
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.classList.contains('price-input')) {
        e.target.closest('form').submit();
    }
});
</script>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
