<?php
/**
 * Footer Management Admin Panel
 * Footer verilerini yönetme paneli
 */

require_once '../config/config.php';
require_once '../config/database.php';


// Sayfa ayarları
$pageTitle = 'Footer Yönetimi';
$pageDescription = 'Website footer verilerini yönetin';
$pageIcon = 'bi bi-shoe-prints';

$message = '';
$messageType = '';

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_contact_info':
                $stmt = $pdo->prepare("UPDATE contact_cards SET contact_info = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$_POST['contact_info'], $_POST['contact_id']]);
                $message = '✅ İletişim bilgileri güncellendi!';
                $messageType = 'success';
                break;
                
            case 'update_office_info':
                $stmt = $pdo->prepare("UPDATE contact_office SET address = ?, working_hours = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$_POST['address'], $_POST['working_hours'], $_POST['office_id']]);
                $message = '✅ Ofis bilgileri güncellendi!';
                $messageType = 'success';
                break;
                
            case 'add_service':
                $stmt = $pdo->prepare("INSERT INTO services (name, slug, description, created_at) VALUES (?, ?, ?, NOW())");
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $_POST['service_name']));
                $stmt->execute([$_POST['service_name'], $slug, $_POST['service_description'] ?? '']);
                $message = '✅ Yeni hizmet eklendi!';
                $messageType = 'success';
                break;
                
            case 'update_service':
                $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$_POST['service_name'], $_POST['service_description'], $_POST['service_id']]);
                $message = '✅ Hizmet güncellendi!';
                $messageType = 'success';
                break;
                
            case 'delete_service':
                $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
                $stmt->execute([$_POST['service_id']]);
                $message = '✅ Hizmet silindi!';
                $messageType = 'success';
                break;
                
            case 'add_category':
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, created_at) VALUES (?, ?, ?, NOW())");
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $_POST['category_name']));
                $stmt->execute([$_POST['category_name'], $slug, $_POST['category_description'] ?? '']);
                $message = '✅ Yeni kategori eklendi!';
                $messageType = 'success';
                break;
                
            case 'update_category':
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$_POST['category_name'], $_POST['category_description'], $_POST['category_id']]);
                $message = '✅ Kategori güncellendi!';
                $messageType = 'success';
                break;
                
            case 'delete_category':
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$_POST['category_id']]);
                $message = '✅ Kategori silindi!';
                $messageType = 'success';
                break;
                
            case 'add_social':
                $stmt = $pdo->prepare("INSERT INTO social_media_links (name, icon, url, display_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$_POST['social_name'], $_POST['social_icon'], $_POST['social_url'] ?: null, $_POST['display_order'] ?? 0, isset($_POST['is_active']) ? 1 : 0]);
                $message = '✅ Yeni sosyal medya platformu eklendi!';
                $messageType = 'success';
                break;
                
            case 'update_social':
                $stmt = $pdo->prepare("UPDATE social_media_links SET name = ?, icon = ?, url = ?, display_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$_POST['social_name'], $_POST['social_icon'], $_POST['social_url'] ?: null, $_POST['display_order'], isset($_POST['is_active']) ? 1 : 0, $_POST['social_id']]);
                $message = '✅ Sosyal medya platformu güncellendi!';
                $messageType = 'success';
                break;
                
            case 'delete_social':
                $stmt = $pdo->prepare("DELETE FROM social_media_links WHERE id = ?");
                $stmt->execute([$_POST['social_id']]);
                $message = '✅ Sosyal medya platformu silindi!';
                $messageType = 'success';
                break;
                
            case 'toggle_social_status':
                $stmt = $pdo->prepare("UPDATE social_media_links SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$_POST['social_id']]);
                $message = '✅ Sosyal medya durumu güncellendi!';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = '❌ Hata: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Verileri çek
try {
    // Hizmetler
    $servicesQuery = "SELECT * FROM services ORDER BY name ASC";
    $servicesStmt = $pdo->prepare($servicesQuery);
    $servicesStmt->execute();
    $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kategoriler
    $categoriesQuery = "SELECT * FROM categories ORDER BY name ASC";
    $categoriesStmt = $pdo->prepare($categoriesQuery);
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // İletişim kartı
    $contactQuery = "SELECT * FROM contact_cards LIMIT 1";
    $contactStmt = $pdo->prepare($contactQuery);
    $contactStmt->execute();
    $contactCard = $contactStmt->fetch(PDO::FETCH_ASSOC);
    
    // Ofis bilgileri
    $officeQuery = "SELECT * FROM contact_office LIMIT 1";
    $officeStmt = $pdo->prepare($officeQuery);
    $officeStmt->execute();
    $officeInfo = $officeStmt->fetch(PDO::FETCH_ASSOC);
    
    // Sosyal medya linklerini al
    $socialQuery = "SELECT * FROM social_media_links ORDER BY display_order ASC, name ASC";
    $socialStmt = $pdo->prepare($socialQuery);
    $socialStmt->execute();
    $socialLinks = $socialStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $services = [];
    $categories = [];
    $contactCard = null;
    $officeInfo = null;
    $socialLinks = [];
}

// Design header include
include '../includes/design_header.php';
?>

<!-- Footer Management Content -->
<style>
/* DESIGN PANEL TAB STYLING */
.nav-tabs {
    border-bottom: none;
}

.nav-tabs .nav-link {
    background: transparent;
    border: none;
    color: rgba(255,255,255,0.7);
    padding: 0.75rem 1.5rem;
    border-radius: 25px 25px 0 0;
    transition: all 0.3s ease;
    margin-right: 0.25rem;
}

.nav-tabs .nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    border: none;
}

.nav-tabs .nav-link.active {
    background: rgba(255,255,255,0.2);
    color: white;
    border: none;
    border-bottom: 2px solid white;
}

.footer-preview {
    background-color: #071e3d;
    background-image: linear-gradient(135deg, #071e3d 0%, #0a2547 100%);
    color: white;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.preview-column {
    padding: 1rem;
    border-right: 1px solid rgba(255,255,255,0.1);
}

.preview-column:last-child {
    border-right: none;
}

.service-item, .category-item {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.edit-form {
    display: none;
    background: #e7f3ff;
    padding: 1rem;
    border-radius: 6px;
    margin-top: 0.5rem;
}

.custom-dropdown {
    position: relative;
    display: inline-block;
}

.custom-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    z-index: 9999;
    min-width: 120px;
    display: none;
}

.custom-dropdown-menu button {
    display: block;
    width: 100%;
    padding: 8px 12px;
    border: none;
    background: white;
    color: #333;
    text-align: left;
    cursor: pointer;
}

.custom-dropdown-menu button:hover {
    background-color: #f8f9fa;
}
</style>

<div class="row">
    <div class="col-12">
        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Footer Preview -->
        <div class="design-card mb-4">
            <div class="design-card-header">
                <h6 class="mb-0"><i class="bi bi-eye me-2"></i>Footer Önizleme</h6>
            </div>
        <div class="card-body p-0">
            <div class="footer-preview">
                <div class="row">
                    <div class="col-lg-3 preview-column">
                        <img src="../assets/images/mrecutuning.png" alt="Logo" style="max-height: 40px; margin-bottom: 1rem;">
                        <h6><?php echo defined('SITE_NAME') ? SITE_NAME : 'Mr ECU'; ?></h6>
                        <p style="font-size: 0.9rem; opacity: 0.8;">Profesyonel ECU hizmetleri...</p>
                    </div>
                    <div class="col-lg-2 preview-column">
                        <h6><i class="bi bi-gear-wide-connected me-2"></i>Hizmetlerimiz</h6>
                        <?php if (!empty($services)): ?>
                            <?php foreach (array_slice($services, 0, 4) as $service): ?>
                                <div style="font-size: 0.85rem; margin-bottom: 0.5rem;">
                                    <i class="bi bi-chevron-right me-1" style="font-size: 0.7rem;"></i>
                                    <?php echo htmlspecialchars($service['name']); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="font-size: 0.85rem; opacity: 0.6;">Henüz hizmet eklenmemiş</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-2 preview-column">
                        <h6><i class="bi bi-box me-2"></i>Ürünlerimiz</h6>
                        <?php if (!empty($categories)): ?>
                            <?php foreach (array_slice($categories, 0, 4) as $category): ?>
                                <div style="font-size: 0.85rem; margin-bottom: 0.5rem;">
                                    <i class="bi bi-chevron-right me-1" style="font-size: 0.7rem;"></i>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="font-size: 0.85rem; opacity: 0.6;">Henüz kategori eklenmemiş</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-2 preview-column">
                        <h6><i class="bi bi-link me-2"></i>Hızlı Bağlantılar</h6>
                        <div style="font-size: 0.85rem;">
                            <div style="margin-bottom: 0.5rem;"><i class="bi bi-chevron-right me-1" style="font-size: 0.7rem;"></i>Ana Sayfa</div>
                            <div style="margin-bottom: 0.5rem;"><i class="bi bi-chevron-right me-1" style="font-size: 0.7rem;"></i>Hakkımızda</div>
                            <div style="margin-bottom: 0.5rem;"><i class="bi bi-chevron-right me-1" style="font-size: 0.7rem;"></i>İletişim</div>
                        </div>
                    </div>
                    <div class="col-lg-3 preview-column">
                        <h6><i class="bi bi-geo-alt me-2"></i>İletişim</h6>
                        <div style="font-size: 0.85rem;">
                            <?php if ($officeInfo && $officeInfo['address']): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <i class="bi bi-geo-alt me-1 text-primary"></i>
                                    <?php echo htmlspecialchars($officeInfo['address']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($officeInfo && $officeInfo['working_hours']): ?>
                                <div style="margin-bottom: 0.5rem;">
                                    <i class="bi bi-clock me-1 text-primary"></i>
                                    <?php echo htmlspecialchars($officeInfo['working_hours']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($contactCard && $contactCard['contact_info']): ?>
                                <div style="background: rgba(255,255,255,0.1); padding: 0.5rem; border-radius: 4px; font-size: 0.8rem;">
                                    <?php echo nl2br(htmlspecialchars($contactCard['contact_info'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="design-card">
    <div class="design-card-header">
    <ul class="nav nav-tabs" id="footerTabs" role="tablist" style="border: none; margin: -1rem -1.5rem 0 -1.5rem; padding: 0 1.5rem;">
                <li class="nav-item" role="presentation">
                <button class="nav-link active text-white" id="services-tab" data-bs-toggle="tab" data-bs-target="#services" type="button" role="tab">
                <i class="bi bi-gear-wide-connected me-2"></i>Hizmetler
                </button>
                </li>
                <li class="nav-item" role="presentation">
                <button class="nav-link text-white" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
                <i class="bi bi-box me-2"></i>Kategoriler
                </button>
                </li>
                <li class="nav-item" role="presentation">
                <button class="nav-link text-white" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact" type="button" role="tab">
                <i class="bi bi-geo-alt me-2"></i>İletişim
                </button>
                </li>
                <li class="nav-item" role="presentation">
                <button class="nav-link text-white" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button" role="tab">
                <i class="bi bi-share me-2"></i>Sosyal Medya
                </button>
                </li>
                <li class="nav-item" role="presentation">
                <button class="nav-link text-white" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab">
                <i class="bi bi-gear me-2"></i>Ayarlar
                </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content" id="footerTabContent">
                
                <!-- Hizmetler Tab -->
                <div class="tab-pane fade show active" id="services" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="mb-0">Footer'da Gösterilecek Hizmetler</h6>
                        <button class="btn-design-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                            <i class="bi bi-plus me-2"></i>Hizmet Ekle
                        </button>
                    </div>
                    
                    <div class="row">
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $service): ?>
                                <div class="col-lg-6 mb-3">
                                    <div class="service-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($service['name']); ?></h6>
                                                <?php if ($service['description']): ?>
                                                    <p class="text-muted mt-2 mb-0" style="font-size: 0.85rem;">
                                                        <?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>
                                                        <?php echo strlen($service['description']) > 100 ? '...' : ''; ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="custom-dropdown">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                        onclick="toggleCustomDropdown('service-<?php echo $service['id']; ?>')">
                                                    <i class="bi bi-ellipsis-v"></i>
                                                </button>
                                                <div id="dropdown-service-<?php echo $service['id']; ?>" class="custom-dropdown-menu">
                                                    <button onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)">
                                                        <i class="bi bi-pencil-square me-2"></i>Düzenle
                                                    </button>
                                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Bu hizmeti silmek istediğinizden emin misiniz?')">
                                                        <input type="hidden" name="action" value="delete_service">
                                                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                                        <button type="submit" style="color: #dc3545;">
                                                            <i class="bi bi-trash me-2"></i>Sil
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="bi bi-gear-wide-connected fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Henüz hizmet eklenmemiş.</p>
                                    <button class="btn-design-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                                        <i class="bi bi-plus me-2"></i>İlk Hizmeti Ekle
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Kategoriler Tab -->
                <div class="tab-pane fade" id="categories" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="mb-0">Footer'da Gösterilecek Ürün Kategorileri</h6>
                        <button class="btn-design-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="bi bi-plus me-2"></i>Kategori Ekle
                        </button>
                    </div>
                    
                    <div class="row">
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <div class="col-lg-6 mb-3">
                                    <div class="category-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($category['name']); ?></h6>
                                                <?php if ($category['description']): ?>
                                                    <p class="text-muted mt-2 mb-0" style="font-size: 0.85rem;">
                                                        <?php echo htmlspecialchars(substr($category['description'], 0, 100)); ?>
                                                        <?php echo strlen($category['description']) > 100 ? '...' : ''; ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="custom-dropdown">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                        onclick="toggleCustomDropdown('category-<?php echo $category['id']; ?>')">
                                                    <i class="bi bi-ellipsis-v"></i>
                                                </button>
                                                <div id="dropdown-category-<?php echo $category['id']; ?>" class="custom-dropdown-menu">
                                                    <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                                        <i class="bi bi-pencil-square me-2"></i>Düzenle
                                                    </button>
                                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?')">
                                                        <input type="hidden" name="action" value="delete_category">
                                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                                        <button type="submit" style="color: #dc3545;">
                                                            <i class="bi bi-trash me-2"></i>Sil
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="bi bi-box fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Henüz kategori eklenmemiş.</p>
                                    <button class="btn-design-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                        <i class="bi bi-plus me-2"></i>İlk Kategoriyi Ekle
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- İletişim Tab -->
                <div class="tab-pane fade" id="contact" role="tabpanel">
                    <div class="row">
                        <!-- İletişim Bilgileri -->
                        <div class="col-lg-6 mb-4">
                            <div class="design-card">
                                <div class="design-card-header">
                                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>İletişim Bilgileri</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_contact_info">
                                        <input type="hidden" name="contact_id" value="<?php echo $contactCard['id'] ?? '1'; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">İletişim Detay Bilgileri</label>
                                            <textarea name="contact_info" class="form-control" rows="6" 
                                                      placeholder="E-posta, telefon ve diğer iletişim bilgilerini girin..."><?php echo htmlspecialchars($contactCard['contact_info'] ?? ''); ?></textarea>
                                            <div class="form-text">Bu bilgiler footer'ın iletişim bölümünde özel kutuda gösterilir.</div>
                                        </div>
                                        
                                        <button type="submit" class="btn-design-primary">
                                            <i class="bi bi-save me-2"></i>İletişim Bilgilerini Kaydet
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ofis Bilgileri -->
                        <div class="col-lg-6 mb-4">
                            <div class="design-card">
                                <div class="design-card-header">
                                    <h6 class="mb-0"><i class="bi bi-building me-2"></i>Ofis Bilgileri</h6>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_office_info">
                                        <input type="hidden" name="office_id" value="<?php echo $officeInfo['id'] ?? '1'; ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Adres</label>
                                            <textarea name="address" class="form-control" rows="3" 
                                                      placeholder="Ofis adresi..."><?php echo htmlspecialchars($officeInfo['address'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Çalışma Saatleri</label>
                                            <input type="text" name="working_hours" class="form-control" 
                                                   placeholder="Örn: Pazartesi-Cuma 09:00-18:00" 
                                                   value="<?php echo htmlspecialchars($officeInfo['working_hours'] ?? ''); ?>">
                                        </div>
                                        
                                        <button type="submit" class="btn-design-primary">
                                            <i class="bi bi-save me-2"></i>Ofis Bilgilerini Kaydet
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sosyal Medya Tab -->
                <div class="tab-pane fade" id="social" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="mb-0">Footer'da Gösterilecek Sosyal Medya Platformları</h6>
                        <button class="btn-design-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSocialModal">
                            <i class="bi bi-plus me-2"></i>Platform Ekle
                        </button>
                    </div>
                    
                    <div class="row">
                        <?php if (!empty($socialLinks)): ?>
                            <?php foreach ($socialLinks as $social): ?>
                                <div class="col-lg-6 mb-3">
                                    <div class="service-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="<?php echo htmlspecialchars($social['icon']); ?> me-2" style="font-size: 1.5rem; color: #071e3d;"></i>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($social['name']); ?></h6>
                                                    <span class="badge bg-<?php echo $social['is_active'] ? 'success' : 'secondary'; ?> ms-2">
                                                        <?php echo $social['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                                    </span>
                                                </div>
                                                <?php if ($social['url']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-link-45deg me-2 text-primary"></i>
                                                        <a href="<?php echo htmlspecialchars($social['url']); ?>" target="_blank" class="text-decoration-none" style="font-size: 0.85rem;">
                                                            <?php 
                                                            $displayUrl = $social['url'];
                                                            if (strlen($displayUrl) > 40) {
                                                                $displayUrl = substr($displayUrl, 0, 40) . '...';
                                                            }
                                                            echo htmlspecialchars($displayUrl);
                                                            ?>
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <small class="text-muted"><i class="bi bi-exclamation-triangle me-1"></i>URL henüz eklenmemiş</small>
                                                <?php endif; ?>
                                                <div class="mt-2">
                                                    <small class="text-muted">Sıralama: <?php echo $social['display_order']; ?></small>
                                                </div>
                                            </div>
                                            <div class="custom-dropdown">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                        onclick="toggleCustomDropdown('social-<?php echo $social['id']; ?>')">
                                                    <i class="bi bi-ellipsis-v"></i>
                                                </button>
                                                <div id="dropdown-social-<?php echo $social['id']; ?>" class="custom-dropdown-menu">
                                                    <button onclick="editSocial(<?php echo htmlspecialchars(json_encode($social)); ?>)">
                                                        <i class="bi bi-pencil-square me-2"></i>Düzenle
                                                    </button>
                                                    <form method="POST" style="margin: 0;">
                                                        <input type="hidden" name="action" value="toggle_social_status">
                                                        <input type="hidden" name="social_id" value="<?php echo $social['id']; ?>">
                                                        <button type="submit">
                                                            <i class="bi bi-<?php echo $social['is_active'] ? 'eye-slash' : 'eye'; ?> me-2"></i>
                                                            <?php echo $social['is_active'] ? 'Pasif Yap' : 'Aktif Yap'; ?>
                                                        </button>
                                                    </form>
                                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Bu sosyal medya platformunu silmek istediğinizden emin misiniz?')">
                                                        <input type="hidden" name="action" value="delete_social">
                                                        <input type="hidden" name="social_id" value="<?php echo $social['id']; ?>">
                                                        <button type="submit" style="color: #dc3545;">
                                                            <i class="bi bi-trash me-2"></i>Sil
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="bi bi-share fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Henüz sosyal medya platformu eklenmemiş.</p>
                                    <button class="btn-design-primary" data-bs-toggle="modal" data-bs-target="#addSocialModal">
                                        <i class="bi bi-plus me-2"></i>İlk Platformu Ekle
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Footer Sosyal Medya Önizleme -->
                    <?php if (!empty($socialLinks)): ?>
                    <div class="mt-4">
                        <div class="design-card">
                            <div class="design-card-header">
                                <h6 class="mb-0"><i class="bi bi-eye me-2"></i>Footer Sosyal Medya Önizleme</h6>
                            </div>
                            <div class="card-body">
                                <div class="p-3 rounded" style="background-color: #071e3d;">
                                    <div class="social-links d-flex">
                                        <?php foreach (array_filter($socialLinks, function($link) { return $link['is_active']; }) as $link): ?>
                                            <?php if ($link['url']): ?>
                                                <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" 
                                                   class="text-white me-3 footer-social-link" title="<?php echo htmlspecialchars($link['name']); ?>" 
                                                   style="font-size: 1.2rem; transition: all 0.3s ease; text-decoration: none;">
                                                    <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-white me-3 footer-social-link opacity-50" 
                                                      title="<?php echo htmlspecialchars($link['name']); ?> (Link yok)" 
                                                      style="font-size: 1.2rem;">
                                                    <i class="<?php echo htmlspecialchars($link['icon']); ?>"></i>
                                                </span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">Bu önizleme footer'da nasıl görüneceğini gösterir</small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Ayarlar Tab -->
                <div class="tab-pane fade" id="settings" role="tabpanel">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="design-card">
                                <div class="design-card-header">
                                    <h6 class="mb-0"><i class="bi bi-palette me-2"></i>Footer Görünüm Ayarları</h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Footer Rengi:</strong> #071e3d (Koyu Mavi) kullanılıyor.
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Logo Dosyası</label>
                                        <div class="input-group">
                                            <span class="input-group-text">assets/images/</span>
                                            <input type="text" class="form-control" value="mrecutuning.png" readonly>
                                            <button class="btn btn-outline-secondary" type="button">
                                                <i class="bi bi-folder-open"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Footer'da kullanılan logo dosyası</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Footer Açıklama Metni</label>
                                        <textarea class="form-control" rows="3" readonly>Profesyonel ECU hizmetleri ile araçlarınızın performansını maksimuma çıkarın. Güvenli, hızlı ve kaliteli çözümler için bizi tercih edin.</textarea>
                                        <div class="form-text">Bu metin footer'ın sol tarafında logo altında gösterilir.</div>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>Not:</strong> Footer genel ayarları config/config.php dosyasından yönetilir.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Social Media Modal -->
<div class="modal fade" id="addSocialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Sosyal Medya Platformu Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_social">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Platform Adı *</label>
                        <input type="text" name="social_name" class="form-control" required placeholder="Örn: Facebook">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bootstrap Icon *</label>
                        <select name="social_icon" class="form-select" required>
                            <option value="">Seçiniz...</option>
                            <option value="bi-facebook">Facebook (bi-facebook)</option>
                            <option value="bi-instagram">Instagram (bi-instagram)</option>
                            <option value="bi-linkedin">LinkedIn (bi-linkedin)</option>
                            <option value="bi-twitter">Twitter (bi-twitter)</option>
                            <option value="bi-youtube">YouTube (bi-youtube)</option>
                            <option value="bi-whatsapp">WhatsApp (bi-whatsapp)</option>
                            <option value="bi-telegram">Telegram (bi-telegram)</option>
                            <option value="bi-tiktok">TikTok (bi-tiktok)</option>
                            <option value="bi-pinterest">Pinterest (bi-pinterest)</option>
                            <option value="bi-snapchat">Snapchat (bi-snapchat)</option>
                            <option value="bi-discord">Discord (bi-discord)</option>
                            <option value="bi-twitch">Twitch (bi-twitch)</option>
                        </select>
                        <div class="form-text">Platformun ikonunu seçin</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL (Opsiyonel)</label>
                        <input type="url" name="social_url" class="form-control" placeholder="https://facebook.com/yourpage">
                        <div class="form-text">Boş bırakılırsa sadece ikon gösterilir</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sıralama</label>
                        <input type="number" name="display_order" class="form-control" value="0" min="0">
                        <div class="form-text">Düşük sayı önce gösterilir</div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="addSocialActive" checked>
                        <label class="form-check-label" for="addSocialActive">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn-design-primary">
                        <i class="bi bi-plus me-2"></i>Platformu Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Social Media Modal -->
<div class="modal fade" id="editSocialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sosyal Medya Platformu Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_social">
                <input type="hidden" name="social_id" id="editSocialId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Platform Adı *</label>
                        <input type="text" name="social_name" id="editSocialName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bootstrap Icon *</label>
                        <select name="social_icon" id="editSocialIcon" class="form-select" required>
                            <option value="">Seçiniz...</option>
                            <option value="bi-facebook">Facebook (bi-facebook)</option>
                            <option value="bi-instagram">Instagram (bi-instagram)</option>
                            <option value="bi-linkedin">LinkedIn (bi-linkedin)</option>
                            <option value="bi-twitter">Twitter (bi-twitter)</option>
                            <option value="bi-youtube">YouTube (bi-youtube)</option>
                            <option value="bi-whatsapp">WhatsApp (bi-whatsapp)</option>
                            <option value="bi-telegram">Telegram (bi-telegram)</option>
                            <option value="bi-tiktok">TikTok (bi-tiktok)</option>
                            <option value="bi-pinterest">Pinterest (bi-pinterest)</option>
                            <option value="bi-snapchat">Snapchat (bi-snapchat)</option>
                            <option value="bi-discord">Discord (bi-discord)</option>
                            <option value="bi-twitch">Twitch (bi-twitch)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL (Opsiyonel)</label>
                        <input type="url" name="social_url" id="editSocialUrl" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sıralama</label>
                        <input type="number" name="display_order" id="editSocialOrder" class="form-control" min="0">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" id="editSocialActive" class="form-check-input">
                        <label class="form-check-label" for="editSocialActive">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn-design-primary">
                        <i class="bi bi-save me-2"></i>Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Hizmet Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_service">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Hizmet Adı *</label>
                        <input type="text" name="service_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="service_description" class="form-control" rows="3" 
                                  placeholder="Hizmet açıklaması (opsiyonel)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn-design-primary">
                        <i class="bi bi-plus me-2"></i>Hizmeti Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hizmet Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_service">
                <input type="hidden" name="service_id" id="editServiceId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Hizmet Adı *</label>
                        <input type="text" name="service_name" id="editServiceName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="service_description" id="editServiceDescription" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn-design-primary">
                        <i class="bi bi-save me-2"></i>Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Kategori Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kategori Adı *</label>
                        <input type="text" name="category_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="category_description" class="form-control" rows="3" 
                                  placeholder="Kategori açıklaması (opsiyonel)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn-design-primary">
                        <i class="bi bi-plus me-2"></i>Kategoriyi Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kategori Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_category">
                <input type="hidden" name="category_id" id="editCategoryId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kategori Adı *</label>
                        <input type="text" name="category_name" id="editCategoryName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="category_description" id="editCategoryDescription" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn-design-primary">
                        <i class="bi bi-save me-2"></i>Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Custom dropdown functionality
function toggleCustomDropdown(dropdownId) {
    // Tüm dropdown'ları kapat
    const allDropdowns = document.querySelectorAll('.custom-dropdown-menu');
    allDropdowns.forEach(dropdown => {
        if (dropdown.id !== 'dropdown-' + dropdownId) {
            dropdown.style.display = 'none';
        }
    });
    
    // Bu dropdown'ı aç/kapat
    const dropdown = document.getElementById('dropdown-' + dropdownId);
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'none' || dropdown.style.display === '' ? 'block' : 'none';
    }
}

// Dışarı tıklamada kapat
document.addEventListener('click', function(event) {
    const customDropdowns = document.querySelectorAll('.custom-dropdown');
    let clickedInsideDropdown = false;
    
    customDropdowns.forEach(dropdown => {
        if (dropdown.contains(event.target)) {
            clickedInsideDropdown = true;
        }
    });
    
    if (!clickedInsideDropdown) {
        const allDropdowns = document.querySelectorAll('.custom-dropdown-menu');
        allDropdowns.forEach(dropdown => {
            dropdown.style.display = 'none';
        });
    }
});

// Edit functions
function editService(service) {
    document.getElementById('editServiceId').value = service.id;
    document.getElementById('editServiceName').value = service.name;
    document.getElementById('editServiceDescription').value = service.description || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editServiceModal'));
    modal.show();
}

function editCategory(category) {
    document.getElementById('editCategoryId').value = category.id;
    document.getElementById('editCategoryName').value = category.name;
    document.getElementById('editCategoryDescription').value = category.description || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

function editSocial(social) {
    document.getElementById('editSocialId').value = social.id;
    document.getElementById('editSocialName').value = social.name;
    document.getElementById('editSocialIcon').value = social.icon;
    document.getElementById('editSocialUrl').value = social.url || '';
    document.getElementById('editSocialOrder').value = social.display_order;
    document.getElementById('editSocialActive').checked = social.is_active == 1;
    
    const modal = new bootstrap.Modal(document.getElementById('editSocialModal'));
    modal.show();
}

// Tab management - refresh preview on tab change
document.querySelectorAll('#footerTabs button[data-bs-toggle="tab"]').forEach(tab => {
    tab.addEventListener('shown.bs.tab', function() {
        // Footer preview'i güncelle
        setTimeout(() => {
            window.location.hash = this.getAttribute('data-bs-target');
        }, 100);
    });
});

// Auto-refresh preview every 30 seconds
setInterval(() => {
    // Live preview refresh - can be implemented later
    console.log('Footer preview refresh...');
}, 30000);
</script>

<?php
// Sayfa özel JavaScript
$pageJS = "
// Footer management specific scripts
console.log('Footer Management Panel loaded');

// Form validation
const forms = document.querySelectorAll('form');
forms.forEach(form => {
    form.addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Lütfen tüm gerekli alanları doldurun.');
        }
    });
});
";

// Design footer include
include '../includes/design_footer.php';
?>