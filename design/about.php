<?php
/**
 * About İçerik Yönetimi - Design Panel (Geliştirilmiş)
 * services-edit.php tasarımı baz alınarak hazırlandı
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Sayfa ayarları
$pageTitle = 'Hakkımızda İçerik Yönetimi';
$pageIcon = 'bi bi-info-circle';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'index.php'],
    ['title' => 'Hakkımızda Yönetimi']
];

// Success/Error mesajları
$message = '';
$messageType = '';

// POST işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_about_content':
                // Resim upload işlemi
                $imagePath = $_POST['current_image_url'] ?? '';
                $deleteOldImage = false;

                if (isset($_FILES['about_image']) && $_FILES['about_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/about/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $fileInfo = pathinfo($_FILES['about_image']['name']);
                    $fileName = 'about_main_' . time() . '.' . strtolower($fileInfo['extension']);
                    $targetPath = $uploadDir . $fileName;

                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    if (!in_array(strtolower($fileInfo['extension']), $allowed)) {
                        throw new Exception("Ana resim: JPG, PNG, WebP, GIF desteklenir.");
                    } elseif ($_FILES['about_image']['size'] > 5 * 1024 * 1024) {
                        throw new Exception("Ana resim 5MB'dan büyük olamaz.");
                    } elseif (move_uploaded_file($_FILES['about_image']['tmp_name'], $targetPath)) {
                        $deleteOldImage = true;
                        $imagePath = 'uploads/about/' . $fileName;
                    } else {
                        throw new Exception("Ana resim yüklenemedi.");
                    }
                }

                if (isset($_POST['delete_about_image']) && $_POST['delete_about_image'] === '1') {
                    $deleteOldImage = true;
                    $imagePath = '';
                }
                
                // Features array'ini oluştur
                $features = [];
                if (!empty($_POST['feature_titles'])) {
                    foreach ($_POST['feature_titles'] as $i => $title) {
                        if (!empty($title)) {
                            $features[] = [
                                'title' => $title,
                                'icon' => $_POST['feature_icons'][$i] ?? 'bi bi-check-circle text-success'
                            ];
                        }
                    }
                }
                
                $stmt = $pdo->prepare("UPDATE about_content SET 
                    title = ?, subtitle = ?, description = ?, main_content = ?, 
                    image_url = ?, features = ?, is_active = ?
                    WHERE id = 1");
                
                $stmt->execute([
                    $_POST['about_title'],
                    $_POST['about_subtitle'],
                    $_POST['about_description'],
                    $_POST['about_main_content'],
                    $imagePath,
                    json_encode($features),
                    isset($_POST['about_is_active']) ? 1 : 0
                ]);
                
                // Eğer kayıt yoksa insert et
                if ($stmt->rowCount() === 0) {
                    $stmt = $pdo->prepare("INSERT INTO about_content 
                        (title, subtitle, description, main_content, image_url, features, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['about_title'],
                        $_POST['about_subtitle'],
                        $_POST['about_description'],
                        $_POST['about_main_content'],
                        $imagePath,
                        json_encode($features),
                        isset($_POST['about_is_active']) ? 1 : 0
                    ]);
                }

                // Eski resmi sil
                if ($deleteOldImage && !empty($_POST['current_image_url']) && $_POST['current_image_url'] !== $imagePath) {
                    $oldFile = '../' . $_POST['current_image_url'];
                    if (file_exists($oldFile) && strpos($_POST['current_image_url'], 'uploads/') === 0) {
                        unlink($oldFile);
                    }
                }
                
                $message = '✅ Ana içerik başarıyla güncellendi!';
                $messageType = 'success';
                break;

            case 'add_core_value':
                $stmt = $pdo->prepare("INSERT INTO about_core_values 
                    (title, description, icon, icon_color, background_color, order_no, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['cv_title'],
                    $_POST['cv_description'],
                    $_POST['cv_icon'],
                    $_POST['cv_icon_color'],
                    $_POST['cv_background_color'],
                    $_POST['cv_order_no'],
                    isset($_POST['cv_is_active']) ? 1 : 0
                ]);
                
                $message = '✅ Temel değer başarıyla eklendi!';
                $messageType = 'success';
                break;
                
            case 'update_core_value':
                $stmt = $pdo->prepare("UPDATE about_core_values SET 
                    title = ?, description = ?, icon = ?, icon_color = ?, 
                    background_color = ?, order_no = ?, is_active = ?
                    WHERE id = ?");
                $stmt->execute([
                    $_POST['cv_title'],
                    $_POST['cv_description'],
                    $_POST['cv_icon'],
                    $_POST['cv_icon_color'],
                    $_POST['cv_background_color'],
                    $_POST['cv_order_no'],
                    isset($_POST['cv_is_active']) ? 1 : 0,
                    $_POST['cv_id']
                ]);
                
                $message = '✅ Temel değer başarıyla güncellendi!';
                $messageType = 'success';
                break;
                
            case 'delete_core_value':
                $stmt = $pdo->prepare("DELETE FROM about_core_values WHERE id = ?");
                $stmt->execute([$_POST['cv_id']]);
                
                $message = '✅ Temel değer başarıyla silindi!';
                $messageType = 'success';
                break;
                
            case 'add_service_feature':
                // Resim upload işlemi
                $iconPath = '';

                if (isset($_FILES['sf_image']) && $_FILES['sf_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/about/features/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $fileInfo = pathinfo($_FILES['sf_image']['name']);
                    $fileName = 'feature_' . time() . '.' . strtolower($fileInfo['extension']);
                    $targetPath = $uploadDir . $fileName;

                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'];
                    if (!in_array(strtolower($fileInfo['extension']), $allowed)) {
                        throw new Exception("Özellik resmi: JPG, PNG, WebP, SVG desteklenir.");
                    } elseif ($_FILES['sf_image']['size'] > 2 * 1024 * 1024) {
                        throw new Exception("Özellik resmi 2MB'dan büyük olamaz.");
                    } elseif (move_uploaded_file($_FILES['sf_image']['tmp_name'], $targetPath)) {
                        $iconPath = 'uploads/about/features/' . $fileName;
                    } else {
                        throw new Exception("Özellik resmi yüklenemedi.");
                    }
                }
                
                $stmt = $pdo->prepare("INSERT INTO about_service_features 
                    (title, description, icon_url, icon, order_no, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['sf_title'],
                    $_POST['sf_description'],
                    $iconPath,
                    $_POST['sf_icon'],
                    $_POST['sf_order_no'],
                    isset($_POST['sf_is_active']) ? 1 : 0
                ]);
                
                $message = '✅ Hizmet özelliği başarıyla eklendi!';
                $messageType = 'success';
                break;
                
            case 'update_service_feature':
                // Resim upload işlemi
                $iconPath = $_POST['current_sf_image'] ?? '';
                $deleteOldIcon = false;

                if (isset($_FILES['sf_image']) && $_FILES['sf_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/about/features/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $fileInfo = pathinfo($_FILES['sf_image']['name']);
                    $fileName = 'feature_' . time() . '.' . strtolower($fileInfo['extension']);
                    $targetPath = $uploadDir . $fileName;

                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'];
                    if (!in_array(strtolower($fileInfo['extension']), $allowed)) {
                        throw new Exception("Özellik resmi: JPG, PNG, WebP, SVG desteklenir.");
                    } elseif ($_FILES['sf_image']['size'] > 2 * 1024 * 1024) {
                        throw new Exception("Özellik resmi 2MB'dan büyük olamaz.");
                    } elseif (move_uploaded_file($_FILES['sf_image']['tmp_name'], $targetPath)) {
                        $deleteOldIcon = true;
                        $iconPath = 'uploads/about/features/' . $fileName;
                    } else {
                        throw new Exception("Özellik resmi yüklenemedi.");
                    }
                }

                if (isset($_POST['delete_sf_image']) && $_POST['delete_sf_image'] === '1') {
                    $deleteOldIcon = true;
                    $iconPath = '';
                }
                
                $stmt = $pdo->prepare("UPDATE about_service_features SET 
                    title = ?, description = ?, icon_url = ?, icon = ?, order_no = ?, is_active = ?
                    WHERE id = ?");
                $stmt->execute([
                    $_POST['sf_title'],
                    $_POST['sf_description'],
                    $iconPath,
                    $_POST['sf_icon'],
                    $_POST['sf_order_no'],
                    isset($_POST['sf_is_active']) ? 1 : 0,
                    $_POST['sf_id']
                ]);

                // Eski resmi sil
                if ($deleteOldIcon && !empty($_POST['current_sf_image']) && $_POST['current_sf_image'] !== $iconPath) {
                    $oldFile = '../' . $_POST['current_sf_image'];
                    if (file_exists($oldFile) && strpos($_POST['current_sf_image'], 'uploads/') === 0) {
                        unlink($oldFile);
                    }
                }
                
                $message = '✅ Hizmet özelliği başarıyla güncellendi!';
                $messageType = 'success';
                break;
                
            case 'delete_service_feature':
                // İlgili resmi de sil
                $stmt = $pdo->prepare("SELECT icon_url FROM about_service_features WHERE id = ?");
                $stmt->execute([$_POST['sf_id']]);
                $feature = $stmt->fetch();
                
                if ($feature && !empty($feature['icon_url']) && strpos($feature['icon_url'], 'uploads/') === 0) {
                    $file = '../' . $feature['icon_url'];
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
                
                $stmt = $pdo->prepare("DELETE FROM about_service_features WHERE id = ?");
                $stmt->execute([$_POST['sf_id']]);
                
                $message = '✅ Hizmet özelliği başarıyla silindi!';
                $messageType = 'success';
                break;
                
            case 'update_vision':
                // Resim upload işlemi
                $visionImagePath = $_POST['current_vision_image'] ?? '';
                $deleteOldVisionImage = false;

                if (isset($_FILES['vision_image']) && $_FILES['vision_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/about/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $fileInfo = pathinfo($_FILES['vision_image']['name']);
                    $fileName = 'vision_' . time() . '.' . strtolower($fileInfo['extension']);
                    $targetPath = $uploadDir . $fileName;

                    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                    if (!in_array(strtolower($fileInfo['extension']), $allowed)) {
                        throw new Exception("Vizyon resmi: JPG, PNG, WebP, GIF desteklenir.");
                    } elseif ($_FILES['vision_image']['size'] > 5 * 1024 * 1024) {
                        throw new Exception("Vizyon resmi 5MB'dan büyük olamaz.");
                    } elseif (move_uploaded_file($_FILES['vision_image']['tmp_name'], $targetPath)) {
                        $deleteOldVisionImage = true;
                        $visionImagePath = 'uploads/about/' . $fileName;
                    } else {
                        throw new Exception("Vizyon resmi yüklenemedi.");
                    }
                }

                if (isset($_POST['delete_vision_image']) && $_POST['delete_vision_image'] === '1') {
                    $deleteOldVisionImage = true;
                    $visionImagePath = '';
                }
                
                $vision_features = [];
                if (!empty($_POST['vision_feature_titles'])) {
                    foreach ($_POST['vision_feature_titles'] as $i => $title) {
                        if (!empty($title)) {
                            $vision_features[] = [
                                'title' => $title,
                                'description' => $_POST['vision_feature_descriptions'][$i] ?? '',
                                'icon' => $_POST['vision_feature_icons'][$i] ?? 'bi bi-rocket text-primary'
                            ];
                        }
                    }
                }
                
                $stmt = $pdo->prepare("UPDATE about_vision SET 
                    title = ?, subtitle = ?, description = ?, main_content = ?, 
                    image_url = ?, features = ?, is_active = ?
                    WHERE id = 1");
                
                $stmt->execute([
                    $_POST['vision_title'],
                    $_POST['vision_subtitle'],
                    $_POST['vision_description'],
                    $_POST['vision_main_content'],
                    $visionImagePath,
                    json_encode($vision_features),
                    isset($_POST['vision_is_active']) ? 1 : 0
                ]);
                
                // Eğer kayıt yoksa insert et
                if ($stmt->rowCount() === 0) {
                    $stmt = $pdo->prepare("INSERT INTO about_vision 
                        (title, subtitle, description, main_content, image_url, features, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['vision_title'],
                        $_POST['vision_subtitle'],
                        $_POST['vision_description'],
                        $_POST['vision_main_content'],
                        $visionImagePath,
                        json_encode($vision_features),
                        isset($_POST['vision_is_active']) ? 1 : 0
                    ]);
                }

                // Eski resmi sil
                if ($deleteOldVisionImage && !empty($_POST['current_vision_image']) && $_POST['current_vision_image'] !== $visionImagePath) {
                    $oldFile = '../' . $_POST['current_vision_image'];
                    if (file_exists($oldFile) && strpos($_POST['current_vision_image'], 'uploads/') === 0) {
                        unlink($oldFile);
                    }
                }
                
                $message = '✅ Vizyon içeriği başarıyla güncellendi!';
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
    // Ana about content
    $about_stmt = $pdo->query("SELECT * FROM about_content WHERE id = 1");
    $about_content = $about_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Core Values
    $values_stmt = $pdo->query("SELECT * FROM about_core_values ORDER BY order_no ASC");
    $core_values = $values_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Service Features
    $features_stmt = $pdo->query("SELECT * FROM about_service_features ORDER BY order_no ASC");
    $service_features = $features_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vision
    $vision_stmt = $pdo->query("SELECT * FROM about_vision WHERE id = 1");
    $vision_content = $vision_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

} catch (PDOException $e) {
    $message = '❌ Veritabanı hatası: ' . $e->getMessage();
    $messageType = 'error';
    $about_content = [];
    $core_values = [];
    $service_features = [];
    $vision_content = [];
}

include '../includes/design_header.php';
?>

<!-- Modern Design - About Content Management -->
<style>
    .card-header {
        background: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        padding: 1rem 1.25rem;
    }
    .btn-design-primary {
        background: #0d6efd;
        border: none;
        color: white;
        padding: 0.6rem 1.5rem;
        font-weight: 600;
        border-radius: 8px;
    }
    .btn-design-primary:hover {
        background: #0b5ed7;
        transform: translateY(-1px);
    }
    .form-control:focus, .form-select:focus {
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        border-color: #0d6efd;
    }
    .input-group-text {
        background: #e9ecef;
    }
    .feature-item .form-control {
        border-radius: 8px 0 0 8px;
    }
    .feature-item .btn-outline-danger {
        border-radius: 0 8px 8px 0;
    }
    .img-thumbnail {
        border-radius: 12px;
    }
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .alert {
        border-radius: 8px;
    }
    .nav-tabs .nav-link {
        border-radius: 8px 8px 0 0;
        border: none;
        background: #f8f9fa;
        margin-right: 0.25rem;
        color: #6c757d;
        font-weight: 500;
    }
    .nav-tabs .nav-link.active {
        background: #0d6efd;
        color: white;
    }
    .tab-content {
        background: white;
        border-radius: 0 12px 12px 12px;
        padding: 1.5rem;
    }
    .value-card, .feature-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    .value-card:hover, .feature-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .image-upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        background: #f8f9fa;
    }
    .image-upload-area:hover {
        border-color: #0d6efd;
        background: rgba(13, 110, 253, 0.05);
    }
    .image-upload-area.dragover {
        border-color: #0d6efd;
        background: rgba(13, 110, 253, 0.1);
        transform: scale(1.02);
    }
</style>

<div class="container-fluid py-4">
    <?php if (!empty($message)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="<?= $pageIcon ?> fs-4 me-3 text-primary"></i>
                        <h4 class="mb-0"><?= $pageTitle ?></h4>
                    </div>
                    <div>
                        <a href="../about.php" target="_blank" class="btn btn-outline-success">
                            <i class="bi bi-external-link-alt me-2"></i>Sayfayı Görüntüle
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Navigation Tabs -->
                    <nav>
                        <div class="nav nav-tabs px-3 pt-3" id="nav-tab" role="tablist">
                            <button class="nav-link active" id="nav-main-tab" data-bs-toggle="tab" data-bs-target="#nav-main" type="button">
                                <i class="bi bi-info-circle me-2"></i>Ana İçerik
                            </button>
                            <button class="nav-link" id="nav-values-tab" data-bs-toggle="tab" data-bs-target="#nav-values" type="button">
                                <i class="bi bi-heart me-2"></i>Temel Değerler
                            </button>
                            <button class="nav-link" id="nav-features-tab" data-bs-toggle="tab" data-bs-target="#nav-features" type="button">
                                <i class="bi bi-concierge-bell me-2"></i>Hizmet Özellikleri
                            </button>
                            <button class="nav-link" id="nav-vision-tab" data-bs-toggle="tab" data-bs-target="#nav-vision" type="button">
                                <i class="bi bi-eye me-2"></i>Vizyon
                            </button>
                        </div>
                    </nav>

                    <!-- Tab Content -->
                    <div class="tab-content" id="nav-tabContent">
                        <!-- ANA İÇERİK TAB -->
                        <div class="tab-pane fade show active" id="nav-main">
                            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="update_about_content">
                                <input type="hidden" name="current_image_url" value="<?php echo htmlspecialchars($about_content['image_url'] ?? ''); ?>">
                                
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="mb-4">
                                            <label for="about_title" class="form-label fw-bold">Başlık *</label>
                                            <input type="text" class="form-control form-control-lg" id="about_title" name="about_title" 
                                                   value="<?php echo htmlspecialchars($about_content['title'] ?? 'Neden Biz?'); ?>" required maxlength="255">
                                        </div>

                                        <div class="mb-4">
                                            <label for="about_subtitle" class="form-label fw-bold">Alt Başlık</label>
                                            <input type="text" class="form-control" id="about_subtitle" name="about_subtitle" 
                                                   value="<?php echo htmlspecialchars($about_content['subtitle'] ?? ''); ?>" maxlength="500">
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="about_description" class="form-label fw-bold">Açıklama *</label>
                                            <textarea class="form-control" id="about_description" name="about_description" rows="4" required 
                                                      maxlength="1000"><?php echo htmlspecialchars($about_content['description'] ?? ''); ?></textarea>
                                            <div class="form-text">Ana açıklama metni (maksimum 1000 karakter)</div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="about_main_content" class="form-label fw-bold">Detaylı İçerik</label>
                                            <textarea class="form-control" id="about_main_content" name="about_main_content" rows="6" 
                                                      style="font-family: 'Courier New', monospace;"><?php echo htmlspecialchars($about_content['main_content'] ?? ''); ?></textarea>
                                            <div class="form-text">Ana içerik metnini buraya yazın</div>
                                        </div>

                                        <!-- Özellikler -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Ana Özellikler</label>
                                            <div id="about-features">
                                                <?php 
                                                $features = !empty($about_content['features']) ? json_decode($about_content['features'], true) : [];
                                                foreach ($features as $i => $feature): 
                                                ?>
                                                    <div class="feature-item mb-2">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" name="feature_titles[]" 
                                                                   placeholder="Özellik adı" value="<?php echo htmlspecialchars($feature['title'] ?? ''); ?>">
                                                            <input type="text" class="form-control" name="feature_icons[]" 
                                                                   placeholder="Icon (örn: bi bi-check-circle text-success)" 
                                                                   value="<?php echo htmlspecialchars($feature['icon'] ?? ''); ?>">
                                                            <button type="button" class="btn btn-outline-danger remove-feature">
                                                                <i class="bi bi-minus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (empty($features)): ?>
                                                    <div class="feature-item mb-2">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" name="feature_titles[]" placeholder="Özellik adı">
                                                            <input type="text" class="form-control" name="feature_icons[]" 
                                                                   placeholder="Icon (örn: bi bi-check-circle text-success)">
                                                            <button type="button" class="btn btn-outline-danger remove-feature" style="display:none;">
                                                                <i class="bi bi-minus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="addMainFeature">
                                                <i class="bi bi-plus me-1"></i>Özellik Ekle
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <!-- Yayınlama Ayarları -->
                                        <div class="card shadow-sm mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0"><i class="bi bi-cog me-2"></i>Ayarlar</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="about_is_active" 
                                                           <?php echo (!isset($about_content['is_active']) || $about_content['is_active']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">
                                                        <i class="bi bi-eye text-success me-1"></i>Aktif
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Ana Resim -->
                                        <div class="card shadow-sm mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0"><i class="bi bi-image me-2"></i>Ana Resim</h6>
                                            </div>
                                            <div class="card-body">
                                                <?php if (!empty($about_content['image_url'])): ?>
                                                    <?php if (strpos($about_content['image_url'], 'uploads/') === 0): ?>
                                                        <img src="../<?php echo htmlspecialchars($about_content['image_url']); ?>" 
                                                             class="img-fluid rounded mb-3" style="max-height: 150px;">
                                                    <?php else: ?>
                                                        <img src="<?php echo htmlspecialchars($about_content['image_url']); ?>" 
                                                             class="img-fluid rounded mb-3" style="max-height: 150px;">
                                                    <?php endif; ?>
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" id="delete_about_image" name="delete_about_image" value="1">
                                                        <label class="form-check-label text-danger" for="delete_about_image">Resmi Sil</label>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="image-upload-area" onclick="document.getElementById('about_image').click();">
                                                    <i class="bi bi-cloud-upload-alt fa-2x text-primary mb-2"></i>
                                                    <p class="mb-0">Resim yüklemek için tıklayın</p>
                                                    <small class="text-muted">JPG, PNG, WebP - max 5MB</small>
                                                </div>
                                                <input type="file" class="form-control d-none" id="about_image" name="about_image" accept="image/*">
                                                
                                                <div id="aboutImagePreview" class="mt-3" style="display:none;">
                                                    <img id="aboutPreviewImg" src="" class="img-fluid rounded" style="max-height: 150px;">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-design-primary btn-lg">
                                                <i class="bi bi-save me-2"></i>Ana İçeriği Kaydet
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- TEMEL DEĞERLER TAB -->
                        <div class="tab-pane fade" id="nav-values">
                            <div class="row">
                                <div class="col-lg-8">
                                    <h5 class="mb-3"><i class="bi bi-heart me-2 text-primary"></i>Mevcut Temel Değerler</h5>
                                    <div id="coreValuesList">
                                        <?php foreach ($core_values as $value): ?>
                                            <div class="value-card">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <div class="<?php echo htmlspecialchars($value['background_color']); ?> p-2 rounded-3 me-3">
                                                                <i class="<?php echo htmlspecialchars($value['icon']); ?> <?php echo htmlspecialchars($value['icon_color']); ?>" style="font-size: 1.5rem;"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0"><?php echo htmlspecialchars($value['title']); ?></h6>
                                                                <small class="text-muted">Sıra: <?php echo $value['order_no']; ?> 
                                                                    <?php if (!$value['is_active']): ?>
                                                                        | <span class="text-warning">Pasif</span>
                                                                    <?php endif; ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <p class="mb-0 small text-muted">
                                                            <?php echo nl2br(htmlspecialchars(substr($value['description'], 0, 120))); ?>
                                                            <?php if (strlen($value['description']) > 120): ?>...<?php endif; ?>
                                                        </p>
                                                    </div>
                                                    <div class="ms-3">
                                                        <button class="btn btn-outline-primary btn-sm me-1" 
                                                                onclick="editCoreValue(<?php echo htmlspecialchars(json_encode($value)); ?>)">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        <form method="POST" class="d-inline" 
                                                              onsubmit="return confirm('Bu değeri silmek istediğinizden emin misiniz?')">
                                                            <input type="hidden" name="action" value="delete_core_value">
                                                            <input type="hidden" name="cv_id" value="<?php echo $value['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($core_values)): ?>
                                            <div class="text-center text-muted py-4">
                                                <i class="bi bi-heart fa-3x mb-3 opacity-25"></i>
                                                <p>Henüz temel değer eklenmemiş.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="card shadow-sm">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="bi bi-plus me-2"></i>Yeni Değer Ekle</h6>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" action="" id="coreValueForm">
                                                <input type="hidden" name="action" value="add_core_value" id="cvAction">
                                                <input type="hidden" name="cv_id" id="cvId">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Başlık *</label>
                                                    <input type="text" class="form-control" name="cv_title" id="cvTitle" required maxlength="255">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Açıklama *</label>
                                                    <textarea class="form-control" name="cv_description" id="cvDescription" rows="3" required></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Icon *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light">
                                                            <i id="cvIconPreview" class="bi bi-heart fs-4"></i>
                                                        </span>
                                                        <input type="text" class="form-control" name="cv_icon" id="cvIcon" 
                                                               value="bi bi-heart" required placeholder="bi bi-heart">
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-6 mb-3">
                                                        <label class="form-label fw-bold">Icon Rengi</label>
                                                        <select class="form-select" name="cv_icon_color" id="cvIconColor">
                                                            <option value="text-primary">Mavi</option>
                                                            <option value="text-success">Yeşil</option>
                                                            <option value="text-warning">Sarı</option>
                                                            <option value="text-danger">Kırmızı</option>
                                                            <option value="text-info">Açık Mavi</option>
                                                            <option value="text-secondary">Gri</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6 mb-3">
                                                        <label class="form-label fw-bold">Arka Plan</label>
                                                        <select class="form-select" name="cv_background_color" id="cvBackgroundColor">
                                                            <option value="bg-primary bg-opacity-10">Mavi</option>
                                                            <option value="bg-success bg-opacity-10">Yeşil</option>
                                                            <option value="bg-warning bg-opacity-10">Sarı</option>
                                                            <option value="bg-danger bg-opacity-10">Kırmızı</option>
                                                            <option value="bg-info bg-opacity-10">Açık Mavi</option>
                                                            <option value="bg-secondary bg-opacity-10">Gri</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Sıra No</label>
                                                    <input type="number" class="form-control" name="cv_order_no" id="cvOrderNo" value="1" min="1">
                                                </div>
                                                
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="cv_is_active" id="cvIsActive" checked>
                                                    <label class="form-check-label fw-bold">Aktif</label>
                                                </div>
                                                
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-design-primary" id="cvSubmitBtn">
                                                        <i class="bi bi-plus me-2"></i>Değer Ekle
                                                    </button>
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary w-100 mt-2 d-none" id="cvCancelBtn" onclick="resetCoreValueForm()">
                                                    İptal
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- HİZMET ÖZELLİKLERİ TAB -->
                        <div class="tab-pane fade" id="nav-features">
                            <div class="row">
                                <div class="col-lg-8">
                                    <h5 class="mb-3"><i class="bi bi-concierge-bell me-2 text-primary"></i>Mevcut Hizmet Özellikleri</h5>
                                    <div id="serviceFeaturesList">
                                        <?php foreach ($service_features as $feature): ?>
                                            <div class="feature-card">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <div class="me-3">
                                                                <?php if (!empty($feature['icon_url'])): ?>
                                                                    <?php if (strpos($feature['icon_url'], 'uploads/') === 0): ?>
                                                                        <img src="../<?php echo htmlspecialchars($feature['icon_url']); ?>" 
                                                                             alt="Icon" style="width: 40px; height: 40px;" class="rounded">
                                                                    <?php else: ?>
                                                                        <img src="<?php echo htmlspecialchars($feature['icon_url']); ?>" 
                                                                             alt="Icon" style="width: 40px; height: 40px;" class="rounded">
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <i class="<?php echo htmlspecialchars($feature['icon']); ?> fs-4 text-primary"></i>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0"><?php echo htmlspecialchars($feature['title']); ?></h6>
                                                                <small class="text-muted">Sıra: <?php echo $feature['order_no']; ?> 
                                                                    <?php if (!$feature['is_active']): ?>
                                                                        | <span class="text-warning">Pasif</span>
                                                                    <?php endif; ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <p class="mb-0 small text-muted">
                                                            <?php echo nl2br(htmlspecialchars(substr($feature['description'], 0, 120))); ?>
                                                            <?php if (strlen($feature['description']) > 120): ?>...<?php endif; ?>
                                                        </p>
                                                    </div>
                                                    <div class="ms-3">
                                                        <button class="btn btn-outline-primary btn-sm me-1" 
                                                                onclick="editServiceFeature(<?php echo htmlspecialchars(json_encode($feature)); ?>)">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        <form method="POST" class="d-inline" 
                                                              onsubmit="return confirm('Bu özelliği silmek istediğinizden emin misiniz?')">
                                                            <input type="hidden" name="action" value="delete_service_feature">
                                                            <input type="hidden" name="sf_id" value="<?php echo $feature['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($service_features)): ?>
                                            <div class="text-center text-muted py-4">
                                                <i class="bi bi-concierge-bell fa-3x mb-3 opacity-25"></i>
                                                <p>Henüz hizmet özelliği eklenmemiş.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="card shadow-sm">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="bi bi-plus me-2"></i>Yeni Özellik Ekle</h6>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" enctype="multipart/form-data" id="serviceFeatureForm">
                                                <input type="hidden" name="action" value="add_service_feature" id="sfAction">
                                                <input type="hidden" name="sf_id" id="sfId">
                                                <input type="hidden" name="current_sf_image" id="currentSfImage">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Başlık *</label>
                                                    <input type="text" class="form-control" name="sf_title" id="sfTitle" required maxlength="255">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Açıklama *</label>
                                                    <textarea class="form-control" name="sf_description" id="sfDescription" rows="3" required></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">FontAwesome Icon</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light">
                                                            <i id="sfIconPreview" class="bi bi-cog fs-4"></i>
                                                        </span>
                                                        <input type="text" class="form-control" name="sf_icon" id="sfIcon" 
                                                               value="bi bi-cog" placeholder="bi bi-cog">
                                                    </div>
                                                    <small class="form-text text-muted">Resim yoksa bu icon kullanılır</small>
                                                </div>

                                                <!-- Özellik Resmi -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Özellik Resmi</label>
                                                    <div id="currentSfImageContainer" style="display:none;">
                                                        <img id="currentSfImagePreview" src="" class="img-fluid rounded mb-3" style="max-height: 100px;">
                                                        <div class="form-check mb-3">
                                                            <input class="form-check-input" type="checkbox" id="delete_sf_image" name="delete_sf_image" value="1">
                                                            <label class="form-check-label text-danger">Resmi Sil</label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="image-upload-area" onclick="document.getElementById('sf_image').click();">
                                                        <i class="bi bi-cloud-upload-alt fa-2x text-primary mb-2"></i>
                                                        <p class="mb-0">Özellik resmi yükle</p>
                                                        <small class="text-muted">PNG, SVG, WebP - max 2MB</small>
                                                    </div>
                                                    <input type="file" class="form-control d-none" id="sf_image" name="sf_image" accept="image/*">
                                                    
                                                    <div id="sfImagePreview" class="mt-3" style="display:none;">
                                                        <img id="sfPreviewImg" src="" class="img-fluid rounded" style="max-height: 100px;">
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Sıra No</label>
                                                    <input type="number" class="form-control" name="sf_order_no" id="sfOrderNo" value="1" min="1">
                                                </div>
                                                
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="sf_is_active" id="sfIsActive" checked>
                                                    <label class="form-check-label fw-bold">Aktif</label>
                                                </div>
                                                
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-design-primary" id="sfSubmitBtn">
                                                        <i class="bi bi-plus me-2"></i>Özellik Ekle
                                                    </button>
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary w-100 mt-2 d-none" id="sfCancelBtn" onclick="resetServiceFeatureForm()">
                                                    İptal
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- VİZYON TAB -->
                        <div class="tab-pane fade" id="nav-vision">
                            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="update_vision">
                                <input type="hidden" name="current_vision_image" value="<?php echo htmlspecialchars($vision_content['image_url'] ?? ''); ?>">
                                
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="mb-4">
                                            <label for="vision_title" class="form-label fw-bold">Başlık *</label>
                                            <input type="text" class="form-control form-control-lg" id="vision_title" name="vision_title" 
                                                   value="<?php echo htmlspecialchars($vision_content['title'] ?? 'Vizyonumuz'); ?>" required maxlength="255">
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="vision_subtitle" class="form-label fw-bold">Alt Başlık</label>
                                            <textarea class="form-control" id="vision_subtitle" name="vision_subtitle" rows="2" 
                                                      maxlength="1000"><?php echo htmlspecialchars($vision_content['subtitle'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="vision_description" class="form-label fw-bold">Açıklama</label>
                                            <textarea class="form-control" id="vision_description" name="vision_description" rows="3" 
                                                      maxlength="1000"><?php echo htmlspecialchars($vision_content['description'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="vision_main_content" class="form-label fw-bold">Ana İçerik</label>
                                            <textarea class="form-control" id="vision_main_content" name="vision_main_content" rows="4" 
                                                      style="font-family: 'Courier New', monospace;"><?php echo htmlspecialchars($vision_content['main_content'] ?? ''); ?></textarea>
                                        </div>

                                        <!-- Vizyon Özellikleri -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Vizyon Özellikleri</label>
                                            <div id="vision-features">
                                                <?php 
                                                $vision_features = !empty($vision_content['features']) ? json_decode($vision_content['features'], true) : [];
                                                foreach ($vision_features as $i => $vf): 
                                                ?>
                                                    <div class="vision-feature-item mb-2">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control" name="vision_feature_titles[]" 
                                                                       placeholder="Başlık" value="<?php echo htmlspecialchars($vf['title'] ?? ''); ?>">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control" name="vision_feature_descriptions[]" 
                                                                       placeholder="Açıklama" value="<?php echo htmlspecialchars($vf['description'] ?? ''); ?>">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <input type="text" class="form-control" name="vision_feature_icons[]" 
                                                                       placeholder="Icon" value="<?php echo htmlspecialchars($vf['icon'] ?? ''); ?>">
                                                            </div>
                                                            <div class="col-md-1">
                                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeVisionFeature(this)">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (empty($vision_features)): ?>
                                                    <div class="vision-feature-item mb-2">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control" name="vision_feature_titles[]" placeholder="Başlık">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <input type="text" class="form-control" name="vision_feature_descriptions[]" placeholder="Açıklama">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <input type="text" class="form-control" name="vision_feature_icons[]" 
                                                                       placeholder="Icon (örn: bi bi-rocket text-primary)">
                                                            </div>
                                                            <div class="col-md-1">
                                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeVisionFeature(this)" style="display:none;">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="addVisionFeature">
                                                <i class="bi bi-plus me-1"></i>Özellik Ekle
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <!-- Yayınlama Ayarları -->
                                        <div class="card shadow-sm mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0"><i class="bi bi-cog me-2"></i>Ayarlar</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="vision_is_active" 
                                                           <?php echo (!isset($vision_content['is_active']) || $vision_content['is_active']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label fw-bold">
                                                        <i class="bi bi-eye text-success me-1"></i>Aktif
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Vizyon Resmi -->
                                        <div class="card shadow-sm mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0"><i class="bi bi-image me-2"></i>Vizyon Resmi</h6>
                                            </div>
                                            <div class="card-body">
                                                <?php if (!empty($vision_content['image_url'])): ?>
                                                    <?php if (strpos($vision_content['image_url'], 'uploads/') === 0): ?>
                                                        <img src="../<?php echo htmlspecialchars($vision_content['image_url']); ?>" 
                                                             class="img-fluid rounded mb-3" style="max-height: 150px;">
                                                    <?php else: ?>
                                                        <img src="<?php echo htmlspecialchars($vision_content['image_url']); ?>" 
                                                             class="img-fluid rounded mb-3" style="max-height: 150px;">
                                                    <?php endif; ?>
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" id="delete_vision_image" name="delete_vision_image" value="1">
                                                        <label class="form-check-label text-danger">Resmi Sil</label>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="image-upload-area" onclick="document.getElementById('vision_image').click();">
                                                    <i class="bi bi-cloud-upload-alt fa-2x text-primary mb-2"></i>
                                                    <p class="mb-0">Vizyon resmi yükle</p>
                                                    <small class="text-muted">JPG, PNG, WebP - max 5MB</small>
                                                </div>
                                                <input type="file" class="form-control d-none" id="vision_image" name="vision_image" accept="image/*">
                                                
                                                <div id="visionImagePreview" class="mt-3" style="display:none;">
                                                    <img id="visionPreviewImg" src="" class="img-fluid rounded" style="max-height: 150px;">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-design-primary btn-lg">
                                                <i class="bi bi-save me-2"></i>Vizyon Kaydet
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Ana özellik yönetimi
document.getElementById('addMainFeature').addEventListener('click', function() {
    const container = document.getElementById('about-features');
    const item = document.createElement('div');
    item.className = 'feature-item mb-2';
    item.innerHTML = `
        <div class="input-group">
            <input type="text" class="form-control" name="feature_titles[]" placeholder="Özellik adı">
            <input type="text" class="form-control" name="feature_icons[]" 
                   placeholder="Icon (örn: bi bi-check-circle text-success)">
            <button type="button" class="btn btn-outline-danger remove-feature">
                <i class="bi bi-minus"></i>
            </button>
        </div>
    `;
    container.appendChild(item);
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-feature')) {
        const container = document.getElementById('about-features');
        if (container.children.length > 1) {
            e.target.closest('.feature-item').remove();
        }
    }
});

// Vizyon özellik yönetimi
document.getElementById('addVisionFeature').addEventListener('click', function() {
    const container = document.getElementById('vision-features');
    const item = document.createElement('div');
    item.className = 'vision-feature-item mb-2';
    item.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <input type="text" class="form-control" name="vision_feature_titles[]" placeholder="Başlık">
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" name="vision_feature_descriptions[]" placeholder="Açıklama">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="vision_feature_icons[]" 
                       placeholder="Icon (örn: bi bi-rocket text-primary)">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeVisionFeature(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(item);
});

function removeVisionFeature(button) {
    const container = document.getElementById('vision-features');
    if (container.children.length > 1) {
        button.closest('.vision-feature-item').remove();
    }
}

// Icon önizlemeler
document.getElementById('cvIcon').addEventListener('input', function() {
    document.getElementById('cvIconPreview').className = this.value.trim() || 'bi bi-heart fs-4';
});

document.getElementById('sfIcon').addEventListener('input', function() {
    document.getElementById('sfIconPreview').className = this.value.trim() || 'bi bi-cog fs-4';
});

// Resim önizlemeleri
['about_image', 'sf_image', 'vision_image'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        let previewId, previewContainer;
        if (id === 'about_image') {
            previewId = 'aboutPreviewImg';
            previewContainer = 'aboutImagePreview';
        } else if (id === 'sf_image') {
            previewId = 'sfPreviewImg';
            previewContainer = 'sfImagePreview';
        } else if (id === 'vision_image') {
            previewId = 'visionPreviewImg';
            previewContainer = 'visionImagePreview';
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
            document.getElementById(previewContainer).style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
});

// Core Value yönetimi
function editCoreValue(value) {
    document.getElementById('cvAction').value = 'update_core_value';
    document.getElementById('cvId').value = value.id;
    document.getElementById('cvTitle').value = value.title;
    document.getElementById('cvDescription').value = value.description;
    document.getElementById('cvIcon').value = value.icon;
    document.getElementById('cvIconColor').value = value.icon_color;
    document.getElementById('cvBackgroundColor').value = value.background_color;
    document.getElementById('cvOrderNo').value = value.order_no;
    document.getElementById('cvIsActive').checked = value.is_active == 1;
    
    // Icon önizlemesini güncelle
    document.getElementById('cvIconPreview').className = value.icon + ' fs-4';
    
    document.getElementById('cvSubmitBtn').innerHTML = '<i class="bi bi-save me-2"></i>Güncelle';
    document.getElementById('cvCancelBtn').classList.remove('d-none');
    
    // Values tab'ına git
    const valuesTab = new bootstrap.Tab(document.getElementById('nav-values-tab'));
    valuesTab.show();
}

function resetCoreValueForm() {
    document.getElementById('coreValueForm').reset();
    document.getElementById('cvAction').value = 'add_core_value';
    document.getElementById('cvId').value = '';
    document.getElementById('cvIcon').value = 'bi bi-heart';
    document.getElementById('cvIconPreview').className = 'bi bi-heart fs-4';
    document.getElementById('cvSubmitBtn').innerHTML = '<i class="bi bi-plus me-2"></i>Değer Ekle';
    document.getElementById('cvCancelBtn').classList.add('d-none');
}

// Service Feature yönetimi
function editServiceFeature(feature) {
    document.getElementById('sfAction').value = 'update_service_feature';
    document.getElementById('sfId').value = feature.id;
    document.getElementById('sfTitle').value = feature.title;
    document.getElementById('sfDescription').value = feature.description;
    document.getElementById('sfIcon').value = feature.icon || 'bi bi-cog';
    document.getElementById('sfOrderNo').value = feature.order_no;
    document.getElementById('sfIsActive').checked = feature.is_active == 1;
    document.getElementById('currentSfImage').value = feature.icon_url || '';
    
    // Mevcut resmi göster
    if (feature.icon_url) {
        const isLocal = feature.icon_url.indexOf('uploads/') === 0;
        const imageSrc = isLocal ? '../' + feature.icon_url : feature.icon_url;
        document.getElementById('currentSfImagePreview').src = imageSrc;
        document.getElementById('currentSfImageContainer').style.display = 'block';
    } else {
        document.getElementById('currentSfImageContainer').style.display = 'none';
    }
    
    // Icon önizlemesini güncelle
    document.getElementById('sfIconPreview').className = (feature.icon || 'bi bi-cog') + ' fs-4';
    
    document.getElementById('sfSubmitBtn').innerHTML = '<i class="bi bi-save me-2"></i>Güncelle';
    document.getElementById('sfCancelBtn').classList.remove('d-none');
    
    // Features tab'ına git
    const featuresTab = new bootstrap.Tab(document.getElementById('nav-features-tab'));
    featuresTab.show();
}

function resetServiceFeatureForm() {
    document.getElementById('serviceFeatureForm').reset();
    document.getElementById('sfAction').value = 'add_service_feature';
    document.getElementById('sfId').value = '';
    document.getElementById('sfIcon').value = 'bi bi-cog';
    document.getElementById('sfIconPreview').className = 'bi bi-cog fs-4';
    document.getElementById('currentSfImage').value = '';
    document.getElementById('currentSfImageContainer').style.display = 'none';
    document.getElementById('sfImagePreview').style.display = 'none';
    document.getElementById('sfSubmitBtn').innerHTML = '<i class="bi bi-plus me-2"></i>Özellik Ekle';
    document.getElementById('sfCancelBtn').classList.add('d-none');
}

// Form validation
document.querySelectorAll('.needs-validation').forEach(form => {
    form.addEventListener('submit', e => {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
});

// Drag & Drop için
document.querySelectorAll('.image-upload-area').forEach(area => {
    area.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    area.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    area.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const fileInputId = this.getAttribute('onclick').match(/getElementById\('([^']+)'\)/)[1];
            const fileInput = document.getElementById(fileInputId);
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change'));
        }
    });
});
</script>

<?php include '../includes/design_footer.php'; ?>