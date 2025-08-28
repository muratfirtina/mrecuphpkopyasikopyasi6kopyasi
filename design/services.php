<?php
/**
 * Design Panel - Services Management
 * Services listing and management page
 */

// Require files
require_once '../config/config.php';
require_once '../config/database.php';

$pageTitle = 'Hizmet Yönetimi';
$pageDescription = 'Hizmetleri görüntüle, ekle, düzenle ve sil';
$pageIcon = 'fas fa-concierge-bell';

$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'index.php'],
    ['title' => 'Hizmet Yönetimi']
];

$message = '';
$messageType = '';

// İşlemler
$action = $_GET['action'] ?? '';

if ($action === 'delete' && isset($_GET['id'])) {
    $serviceId = (int)$_GET['id'];
    
    try {
        // Önce hizmetin var olduğunu kontrol et
        $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
        $stmt->execute([$serviceId]);
        $service = $stmt->fetch();
        
        if ($service) {
            // Resim dosyasını sil (varsa)
            if ($service['image'] && file_exists('../' . $service['image'])) {
                unlink('../' . $service['image']);
            }
            
            // Hizmeti sil
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$serviceId]);
            
            $message = "Hizmet başarıyla silindi.";
            $messageType = "success";
        } else {
            $message = "Silinecek hizmet bulunamadı.";
            $messageType = "error";
        }
    } catch (Exception $e) {
        $message = "Silme işleminde hata: " . $e->getMessage();
        $messageType = "error";
        error_log('Service deletion error: ' . $e->getMessage());
    }
}

if ($action === 'toggle_status' && isset($_GET['id'])) {
    $serviceId = (int)$_GET['id'];
    
    try {
        // Mevcut durumu al
        $stmt = $pdo->prepare("SELECT status FROM services WHERE id = ?");
        $stmt->execute([$serviceId]);
        $currentStatus = $stmt->fetch()['status'] ?? '';
        
        if ($currentStatus) {
            $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
            
            $stmt = $pdo->prepare("UPDATE services SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $serviceId]);
            
            $message = "Hizmet durumu başarıyla güncellendi.";
            $messageType = "success";
        }
    } catch (Exception $e) {
        $message = "Durum güncelleme hatası: " . $e->getMessage();
        $messageType = "error";
    }
}

if ($action === 'toggle_featured' && isset($_GET['id'])) {
    $serviceId = (int)$_GET['id'];
    
    try {
        // Mevcut durumu al
        $stmt = $pdo->prepare("SELECT is_featured FROM services WHERE id = ?");
        $stmt->execute([$serviceId]);
        $currentFeatured = $stmt->fetch()['is_featured'] ?? 0;
        
        $newFeatured = $currentFeatured ? 0 : 1;
        
        $stmt = $pdo->prepare("UPDATE services SET is_featured = ? WHERE id = ?");
        $stmt->execute([$newFeatured, $serviceId]);
        
        $message = "Öne çıkan durumu başarıyla güncellendi.";
        $messageType = "success";
    } catch (Exception $e) {
        $message = "Öne çıkan durum güncelleme hatası: " . $e->getMessage();
        $messageType = "error";
    }
}

// Get services
try {
    $stmt = $pdo->query("
        SELECT * FROM services 
        ORDER BY sort_order ASC, name ASC
    ");
    $services = $stmt->fetchAll();
} catch (Exception $e) {
    $services = [];
    error_log('Services fetch error: ' . $e->getMessage());
}

// Include header
require_once '../includes/design_header.php';
?>

<!-- Services Management Content -->
<div class="design-card">
    <div class="design-card-header d-flex justify-content-between align-items-center">
        <h3><i class="fas fa-concierge-bell me-2"></i>Hizmet Yönetimi</h3>
        <div class="btn-group">
            <a href="services-add.php" class="btn btn-design-primary">
                <i class="fas fa-plus me-2"></i>Yeni Hizmet Ekle
            </a>
            <a href="../services.php" target="_blank" class="btn btn-outline-info">
                <i class="fas fa-eye me-2"></i>Önizleme
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Services Table -->
        <?php if (!empty($services)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 60px;">İkon</th>
                            <th>Hizmet Adı</th>
                            <th style="width: 120px;">Fiyat</th>
                            <th style="width: 100px;">Durum</th>
                            <th style="width: 80px;">Öne Çıkan</th>
                            <th style="width: 80px;">Sıralama</th>
                            <th style="width: 140px;">Oluşturulma</th>
                            <th style="width: 200px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo $service['id']; ?></td>
                                <td class="text-center">
                                    <i class="<?php echo htmlspecialchars($service['icon']); ?> text-primary" style="font-size: 1.2rem;"></i>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($service['name']); ?></div>
                                    <small class="text-muted">/hizmet/<?php echo htmlspecialchars($service['slug']); ?></small>
                                    <br><small class="text-muted">
                                        <?php echo htmlspecialchars(substr($service['description'], 0, 80)) . '...'; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($service['price_from']): ?>
                                        <span class="fw-bold text-success"><?php echo number_format($service['price_from'], 2); ?> TL</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?action=toggle_status&id=<?php echo $service['id']; ?>" 
                                       class="btn btn-sm <?php echo $service['status'] === 'active' ? 'btn-success' : 'btn-secondary'; ?>" 
                                       onclick="return confirm('Durumu değiştirmek istediğinizden emin misiniz?')">
                                        <?php echo $service['status'] === 'active' ? 'Aktif' : 'Pasif'; ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="?action=toggle_featured&id=<?php echo $service['id']; ?>" 
                                       class="btn btn-sm <?php echo $service['is_featured'] ? 'btn-warning' : 'btn-outline-warning'; ?>" 
                                       onclick="return confirm('Öne çıkan durumunu değiştirmek istediğinizden emin misiniz?')">
                                        <?php if ($service['is_featured']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?php echo $service['sort_order'] ?? '-'; ?></span>
                                </td>
                                <td>
                                    <small><?php echo date('d.m.Y H:i', strtotime($service['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="../hizmet/<?php echo urlencode($service['slug']); ?>" 
                                           class="btn btn-outline-info" 
                                           title="Önizleme" 
                                           target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="services-edit.php?id=<?php echo $service['id']; ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $service['id']; ?>" 
                                           class="btn btn-outline-danger" 
                                           title="Sil"
                                           onclick="return confirm('Bu hizmeti silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center p-5">
                <i class="fas fa-concierge-bell text-muted mb-3" style="font-size: 4rem;"></i>
                <h4 class="text-muted">Henüz hizmet bulunmuyor</h4>
                <p class="text-muted">İlk hizmetinizi eklemek için aşağıdaki butona tıklayın.</p>
                <a href="services-add.php" class="btn btn-design-primary">
                    <i class="fas fa-plus me-2"></i>Yeni Hizmet Ekle
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Cards -->
<?php if (!empty($services)): ?>
<div class="row mt-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?php echo count($services); ?></div>
            <div class="stat-label">Toplam Hizmet</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?php echo count(array_filter($services, fn($s) => $s['status'] === 'active')); ?></div>
            <div class="stat-label">Aktif Hizmet</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?php echo count(array_filter($services, fn($s) => $s['is_featured'])); ?></div>
            <div class="stat-label">Öne Çıkan</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?php echo count(array_filter($services, fn($s) => !empty($s['price_from']))); ?></div>
            <div class="stat-label">Fiyatlı Hizmet</div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
require_once '../includes/design_footer.php';
?>
