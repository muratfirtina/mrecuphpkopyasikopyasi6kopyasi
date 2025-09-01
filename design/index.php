<?php
/**
 * Design Panel - Ana Dashboard
 * Sadece Admin ve Design rollerine sahip kullanıcılar erişebilir
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Sayfa bilgileri
$pageTitle = 'Dashboard';
$pageDescription = 'Design Panel - Ana kontrol merkezi';
$breadcrumbs = [
    ['title' => 'Design Panel', 'url' => 'index.php'],
    ['title' => 'Dashboard']
];

// Header include
include '../includes/design_header.php';

// İstatistikleri al
try {
    // Toplam slider sayısı
    $stmt = $pdo->query("SELECT COUNT(*) FROM design_sliders");
    $totalSliders = $stmt->fetchColumn();
    
    // Aktif slider sayısı
    $stmt = $pdo->query("SELECT COUNT(*) FROM design_sliders WHERE is_active = 1");
    $activeSliders = $stmt->fetchColumn();
    
    // Toplam ayar sayısı
    $stmt = $pdo->query("SELECT COUNT(*) FROM design_settings");
    $totalSettings = $stmt->fetchColumn();
    
    // Toplam içerik sayısı
    $stmt = $pdo->query("SELECT COUNT(*) FROM content_management");
    $totalContent = $stmt->fetchColumn();
    
    // Son güncellemeler
    $stmt = $pdo->query("
        SELECT 'slider' as type, title as name, updated_at, is_active 
        FROM design_sliders 
        ORDER BY updated_at DESC 
        LIMIT 5
    ");
    $recentUpdates = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Verileri alırken hata oluştu: " . $e->getMessage();
}
?>

<!-- Dashboard Content -->
<div class="row mb-4">
    <div class="col-12">
        <div class="design-card">
            <div class="design-card-header">
                <h5 class="mb-0">
                    <i class="bi bi-chart-line me-2"></i>Design Panel Dashboard
                </h5>
            </div>
            <div class="card-body">
                <p class="mb-0">Site tasarımını yönetmek için gerekli tüm araçlar burada. Slider, ayarlar ve içeriklerinizi kolayca düzenleyebilirsiniz.</p>
            </div>
        </div>
    </div>
</div>

<!-- İstatistik Kartları -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <div class="stat-icon bg-primary text-white rounded-circle p-3">
                        <i class="bi bi-images fa-xl"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="stat-number"><?php echo $totalSliders ?? 0; ?></div>
                    <div class="stat-label">Toplam Slider</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <div class="stat-icon bg-success text-white rounded-circle p-3">
                        <i class="bi bi-check-circle fa-xl"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="stat-number"><?php echo $activeSliders ?? 0; ?></div>
                    <div class="stat-label">Aktif Slider</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <div class="stat-icon bg-info text-white rounded-circle p-3">
                        <i class="bi bi-cog fa-xl"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="stat-number"><?php echo $totalSettings ?? 0; ?></div>
                    <div class="stat-label">Ayarlar</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0">
                    <div class="stat-icon bg-warning text-white rounded-circle p-3">
                        <i class="bi bi-pencil-square fa-xl"></i>
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="stat-number"><?php echo $totalContent ?? 0; ?></div>
                    <div class="stat-label">İçerik</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ana İşlem Kartları -->
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="design-card h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="bi bi-images text-primary" style="font-size: 3rem;"></i>
                </div>
                <h5 class="card-title">Hero Slider Yönetimi</h5>
                <p class="card-text text-muted">Ana sayfadaki hero slider'ları düzenleyin, resim ve metinleri değiştirin.</p>
                <a href="sliders.php" class="btn btn-design-primary">
                    <i class="bi bi-arrow-right me-2"></i>Slider Yönet
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="design-card h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="bi bi-palette text-success" style="font-size: 3rem;"></i>
                </div>
                <h5 class="card-title">Site Ayarları</h5>
                <p class="card-text text-muted">Renk şeması, logo ve genel site ayarlarını düzenleyin.</p>
                <a href="settings.php" class="btn btn-outline-success">
                    <i class="bi bi-arrow-right me-2"></i>Ayarlar
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="design-card h-100">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <i class="bi bi-pencil-square text-info" style="font-size: 3rem;"></i>
                </div>
                <h5 class="card-title">İçerik Yönetimi</h5>
                <p class="card-text text-muted">Sayfa içeriklerini, metinleri ve diğer elemanları düzenleyin.</p>
                <a href="content.php" class="btn btn-outline-info">
                    <i class="bi bi-arrow-right me-2"></i>İçerik Düzenle
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Son Güncellemeler ve Hızlı Eylemler -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="design-card">
            <div class="design-card-header">
                <h5 class="mb-0">
                    <i class="bi bi-clock me-2"></i>Son Güncellemeler
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentUpdates)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentUpdates as $update): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($update['name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo ucfirst($update['type']); ?> - 
                                        <?php echo date('d.m.Y H:i', strtotime($update['updated_at'])); ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?php echo $update['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $update['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-pencil text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-2">Henüz güncelleme bulunmuyor</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="design-card">
            <div class="design-card-header">
                <h5 class="mb-0">
                    <i class="bi bi-bolt me-2"></i>Hızlı Eylemler
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="sliders.php?action=add" class="btn btn-outline-primary">
                        <i class="bi bi-plus me-2"></i>Yeni Slider Ekle
                    </a>
                    <a href="../index.php" target="_blank" class="btn btn-outline-success">
                        <i class="bi bi-eye me-2"></i>Site Önizleme
                    </a>
                    <a href="content.php" class="btn btn-outline-info">
                        <i class="bi bi-pencil-square me-2"></i>İçerik Düzenle
                    </a>
                    <a href="settings.php" class="btn btn-outline-warning">
                        <i class="bi bi-palette me-2"></i>Tema Ayarları
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Sistem Durumu -->
        <div class="design-card mt-3">
            <div class="design-card-header">
                <h5 class="mb-0">
                    <i class="bi bi-server me-2"></i>Sistem Durumu
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <i class="bi bi-database text-success"></i>
                            <small class="d-block">Database</small>
                            <small class="text-success">Çevrimiçi</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <i class="bi bi-images text-success"></i>
                            <small class="d-block">Medya</small>
                            <small class="text-success">Çalışıyor</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <i class="bi bi-shield-alt text-success"></i>
                            <small class="d-block">Güvenlik</small>
                            <small class="text-success">Güvenli</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded p-2">
                            <i class="bi bi-tachometer-alt text-warning"></i>
                            <small class="d-block">Performans</small>
                            <small class="text-warning">İyi</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bilgilendirme Kartı -->
<div class="row mt-4">
    <div class="col-12">
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            <div>
                <strong>İpucu:</strong> Değişikliklerinizi yaptıktan sonra site önizlemesini kontrol etmeyi unutmayın. 
                Tüm değişiklikler anlık olarak yansıtılır.
            </div>
        </div>
    </div>
</div>

<style>
.stat-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.list-group-item {
    border: none;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.list-group-item:last-child {
    border-bottom: none;
}

.card-body .btn {
    transition: all 0.3s ease;
}

.card-body .btn:hover {
    transform: translateY(-2px);
}
</style>

<?php
// Footer include
include '../includes/design_footer.php';
?>
