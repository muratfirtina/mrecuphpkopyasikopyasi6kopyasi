<?php
/**
 * Contact İçerik Yönetimi - Design Panel
 * services-edit.php tasarımı baz alınarak hazırlandı
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Sayfa ayarları
$pageTitle = 'İletişim İçerik Yönetimi';
$pageIcon = 'bi bi-phone';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'index.php'],
    ['title' => 'İletişim Yönetimi']
];

// Success/Error mesajları
$message = '';
$messageType = '';

// POST işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_settings':
                $stmt = $pdo->prepare("UPDATE contact_settings SET 
                    page_title = ?, page_description = ?, header_title = ?, header_subtitle = ?, 
                    google_maps_embed = ?, form_success_message = ?, privacy_policy_content = ?, is_active = ?
                    WHERE id = 1");
                
                $stmt->execute([
                    $_POST['page_title'],
                    $_POST['page_description'],
                    $_POST['header_title'],
                    $_POST['header_subtitle'],
                    $_POST['google_maps_embed'],
                    $_POST['form_success_message'],
                    $_POST['privacy_policy_content'],
                    isset($_POST['settings_is_active']) ? 1 : 0
                ]);
                
                // Eğer kayıt yoksa insert et
                if ($stmt->rowCount() === 0) {
                    $stmt = $pdo->prepare("INSERT INTO contact_settings 
                        (page_title, page_description, header_title, header_subtitle, google_maps_embed, form_success_message, privacy_policy_content, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['page_title'],
                        $_POST['page_description'],
                        $_POST['header_title'],
                        $_POST['header_subtitle'],
                        $_POST['google_maps_embed'],
                        $_POST['form_success_message'],
                        $_POST['privacy_policy_content'],
                        isset($_POST['settings_is_active']) ? 1 : 0
                    ]);
                }
                
                $message = '✅ Genel ayarlar başarıyla güncellendi!';
                $messageType = 'success';
                break;

            case 'add_contact_card':
                $stmt = $pdo->prepare("INSERT INTO contact_cards 
                    (title, description, icon, icon_color, contact_info, contact_link, button_text, button_color, availability_text, order_no, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['card_title'],
                    $_POST['card_description'],
                    $_POST['card_icon'],
                    $_POST['card_icon_color'],
                    $_POST['card_contact_info'],
                    $_POST['card_contact_link'],
                    $_POST['card_button_text'],
                    $_POST['card_button_color'],
                    $_POST['card_availability_text'],
                    $_POST['card_order_no'],
                    isset($_POST['card_is_active']) ? 1 : 0
                ]);
                
                $message = '✅ İletişim kartı başarıyla eklendi!';
                $messageType = 'success';
                break;
                
            case 'update_contact_card':
                $stmt = $pdo->prepare("UPDATE contact_cards SET 
                    title = ?, description = ?, icon = ?, icon_color = ?, contact_info = ?, 
                    contact_link = ?, button_text = ?, button_color = ?, availability_text = ?, order_no = ?, is_active = ?
                    WHERE id = ?");
                $stmt->execute([
                    $_POST['card_title'],
                    $_POST['card_description'],
                    $_POST['card_icon'],
                    $_POST['card_icon_color'],
                    $_POST['card_contact_info'],
                    $_POST['card_contact_link'],
                    $_POST['card_button_text'],
                    $_POST['card_button_color'],
                    $_POST['card_availability_text'],
                    $_POST['card_order_no'],
                    isset($_POST['card_is_active']) ? 1 : 0,
                    $_POST['card_id']
                ]);
                
                $message = '✅ İletişim kartı başarıyla güncellendi!';
                $messageType = 'success';
                break;
                
            case 'delete_contact_card':
                $stmt = $pdo->prepare("DELETE FROM contact_cards WHERE id = ?");
                $stmt->execute([$_POST['card_id']]);
                
                $message = '✅ İletişim kartı başarıyla silindi!';
                $messageType = 'success';
                break;
                
            case 'update_office_info':
                $stmt = $pdo->prepare("UPDATE contact_office SET 
                    title = ?, description = ?, address = ?, working_hours = ?, transportation = ?, 
                    google_maps_link = ?, is_active = ?
                    WHERE id = 1");
                
                $stmt->execute([
                    $_POST['office_title'],
                    $_POST['office_description'],
                    $_POST['office_address'],
                    $_POST['office_working_hours'],
                    $_POST['office_transportation'],
                    $_POST['office_google_maps_link'],
                    isset($_POST['office_is_active']) ? 1 : 0
                ]);
                
                // Eğer kayıt yoksa insert et
                if ($stmt->rowCount() === 0) {
                    $stmt = $pdo->prepare("INSERT INTO contact_office 
                        (title, description, address, working_hours, transportation, google_maps_link, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['office_title'],
                        $_POST['office_description'],
                        $_POST['office_address'],
                        $_POST['office_working_hours'],
                        $_POST['office_transportation'],
                        $_POST['office_google_maps_link'],
                        isset($_POST['office_is_active']) ? 1 : 0
                    ]);
                }
                
                $message = '✅ Ofis bilgileri başarıyla güncellendi!';
                $messageType = 'success';
                break;
                
            case 'update_form_settings':
                $subject_options = [];
                if (!empty($_POST['subject_options'])) {
                    foreach ($_POST['subject_options'] as $option) {
                        $option = trim($option);
                        if (!empty($option)) {
                            $subject_options[] = $option;
                        }
                    }
                }
                
                $stmt = $pdo->prepare("UPDATE contact_form_settings SET 
                    form_title = ?, form_subtitle = ?, success_message = ?, subject_options = ?, 
                    enable_privacy_checkbox = ?, is_active = ?
                    WHERE id = 1");
                
                $stmt->execute([
                    $_POST['form_title'],
                    $_POST['form_subtitle'],
                    $_POST['form_success_message'],
                    json_encode($subject_options),
                    isset($_POST['enable_privacy_checkbox']) ? 1 : 0,
                    isset($_POST['form_is_active']) ? 1 : 0
                ]);
                
                // Eğer kayıt yoksa insert et
                if ($stmt->rowCount() === 0) {
                    $stmt = $pdo->prepare("INSERT INTO contact_form_settings 
                        (form_title, form_subtitle, success_message, subject_options, enable_privacy_checkbox, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['form_title'],
                        $_POST['form_subtitle'],
                        $_POST['form_success_message'],
                        json_encode($subject_options),
                        isset($_POST['enable_privacy_checkbox']) ? 1 : 0,
                        isset($_POST['form_is_active']) ? 1 : 0
                    ]);
                }
                
                $message = '✅ Form ayarları başarıyla güncellendi!';
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
    // Contact Settings
    $settings_stmt = $pdo->query("SELECT * FROM contact_settings WHERE id = 1");
    $contact_settings = $settings_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Contact Cards
    $cards_stmt = $pdo->query("SELECT * FROM contact_cards ORDER BY order_no ASC");
    $contact_cards = $cards_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Office Info
    $office_stmt = $pdo->query("SELECT * FROM contact_office WHERE id = 1");
    $office_info = $office_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Form Settings
    $form_stmt = $pdo->query("SELECT * FROM contact_form_settings WHERE id = 1");
    $form_settings = $form_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Contact Messages (son 5 mesaj)
    $messages_stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
    $recent_messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = '❌ Veritabanı hatası: ' . $e->getMessage();
    $messageType = 'error';
    $contact_settings = [];
    $contact_cards = [];
    $office_info = [];
    $form_settings = [];
    $recent_messages = [];
}

include '../includes/design_header.php';
?>

<!-- Modern Design - Contact Content Management -->
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
    .contact-card-preview {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }
    .contact-card-preview:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
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
                        <a href="../contact.php" target="_blank" class="btn btn-outline-success">
                            <i class="bi bi-external-link-alt me-2"></i>Sayfayı Görüntüle
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Navigation Tabs -->
                    <nav>
                        <div class="nav nav-tabs px-3 pt-3" id="nav-tab" role="tablist">
                            <button class="nav-link active" id="nav-settings-tab" data-bs-toggle="tab" data-bs-target="#nav-settings" type="button">
                                <i class="bi bi-cog me-2"></i>Genel Ayarlar
                            </button>
                            <button class="nav-link" id="nav-cards-tab" data-bs-toggle="tab" data-bs-target="#nav-cards" type="button">
                                <i class="bi bi-address-card me-2"></i>İletişim Kartları
                            </button>
                            <button class="nav-link" id="nav-office-tab" data-bs-toggle="tab" data-bs-target="#nav-office" type="button">
                                <i class="bi bi-building me-2"></i>Ofis Bilgileri
                            </button>
                            <button class="nav-link" id="nav-form-tab" data-bs-toggle="tab" data-bs-target="#nav-form" type="button">
                                <i class="bi bi-envelope me-2"></i>Form Ayarları
                            </button>
                            <button class="nav-link" id="nav-messages-tab" data-bs-toggle="tab" data-bs-target="#nav-messages" type="button">
                                <i class="bi bi-comments me-2"></i>Mesajlar
                            </button>
                        </div>
                    </nav>

                    <!-- Tab Content -->
                    <div class="tab-content" id="nav-tabContent">
                        <!-- GENEL AYARLAR TAB -->
                        <div class="tab-pane fade show active" id="nav-settings">
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="update_settings">
                                
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="mb-4">
                                            <label for="page_title" class="form-label fw-bold">Sayfa Başlığı *</label>
                                            <input type="text" class="form-control form-control-lg" id="page_title" name="page_title" 
                                                   value="<?php echo htmlspecialchars($contact_settings['page_title'] ?? 'İletişim'); ?>" required maxlength="255">
                                        </div>

                                        <div class="mb-4">
                                            <label for="page_description" class="form-label fw-bold">Sayfa Açıklaması</label>
                                            <textarea class="form-control" id="page_description" name="page_description" rows="3" 
                                                      maxlength="500"><?php echo htmlspecialchars($contact_settings['page_description'] ?? ''); ?></textarea>
                                            <div class="form-text">SEO için kullanılır (maksimum 500 karakter)</div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <label for="header_title" class="form-label fw-bold">Header Başlık *</label>
                                                <input type="text" class="form-control" id="header_title" name="header_title" 
                                                       value="<?php echo htmlspecialchars($contact_settings['header_title'] ?? 'İletişim'); ?>" required maxlength="255">
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <label for="header_subtitle" class="form-label fw-bold">Header Alt Başlık</label>
                                                <input type="text" class="form-control" id="header_subtitle" name="header_subtitle" 
                                                       value="<?php echo htmlspecialchars($contact_settings['header_subtitle'] ?? ''); ?>" maxlength="500">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="google_maps_embed" class="form-label fw-bold">Google Maps Embed Kodu</label>
                                            <textarea class="form-control" id="google_maps_embed" name="google_maps_embed" rows="4" 
                                                      style="font-family: 'Courier New', monospace;" placeholder="<iframe src=...></iframe>"><?php echo htmlspecialchars($contact_settings['google_maps_embed'] ?? ''); ?></textarea>
                                            <div class="form-text">Google Maps'ten alacağınız iframe kodunu buraya yapıştırın</div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="form_success_message" class="form-label fw-bold">Form Başarı Mesajı</label>
                                            <textarea class="form-control" id="form_success_message" name="form_success_message" rows="2" 
                                                      maxlength="500"><?php echo htmlspecialchars($contact_settings['form_success_message'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-4">
                                            <label for="privacy_policy_content" class="form-label fw-bold">Gizlilik Politikası İçeriği</label>
                                            <textarea class="form-control" id="privacy_policy_content" name="privacy_policy_content" rows="6" 
                                                      style="font-family: 'Courier New', monospace;"><?php echo htmlspecialchars($contact_settings['privacy_policy_content'] ?? ''); ?></textarea>
                                            <div class="form-text">HTML kodları desteklenir</div>
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
                                                    <input class="form-check-input" type="checkbox" name="settings_is_active" 
                                                           <?php echo (!isset($contact_settings['is_active']) || $contact_settings['is_active']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">
                                                        <i class="bi bi-eye text-success me-1"></i>Aktif
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-design-primary btn-lg">
                                                <i class="bi bi-save me-2"></i>Genel Ayarları Kaydet
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- İLETİŞİM KARTLARI TAB -->
                        <div class="tab-pane fade" id="nav-cards">
                            <div class="row">
                                <div class="col-lg-8">
                                    <h5 class="mb-3"><i class="bi bi-address-card me-2 text-primary"></i>Mevcut İletişim Kartları</h5>
                                    <div id="contactCardsList">
                                        <?php foreach ($contact_cards as $card): ?>
                                            <div class="contact-card-preview">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <div class="me-3">
                                                                <i class="<?php echo htmlspecialchars($card['icon']); ?> <?php echo htmlspecialchars($card['icon_color']); ?> fa-2x"></i>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-0"><?php echo htmlspecialchars($card['title']); ?></h6>
                                                                <small class="text-muted">
                                                                    Sıra: <?php echo $card['order_no']; ?> | 
                                                                    <?php echo htmlspecialchars($card['contact_info']); ?>
                                                                    <?php if (!$card['is_active']): ?>
                                                                        | <span class="text-warning">Pasif</span>
                                                                    <?php endif; ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <p class="mb-0 small text-muted">
                                                            <?php echo nl2br(htmlspecialchars(substr($card['description'], 0, 120))); ?>
                                                            <?php if (strlen($card['description']) > 120): ?>...<?php endif; ?>
                                                        </p>
                                                    </div>
                                                    <div class="ms-3">
                                                        <button class="btn btn-outline-primary btn-sm me-1" 
                                                                onclick="editContactCard(<?php echo htmlspecialchars(json_encode($card)); ?>)">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        <form method="POST" class="d-inline" 
                                                              onsubmit="return confirm('Bu kartı silmek istediğinizden emin misiniz?')">
                                                            <input type="hidden" name="action" value="delete_contact_card">
                                                            <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($contact_cards)): ?>
                                            <div class="text-center text-muted py-4">
                                                <i class="bi bi-address-card fa-3x mb-3 opacity-25"></i>
                                                <p>Henüz iletişim kartı eklenmemiş.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="card shadow-sm">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="bi bi-plus me-2"></i>Yeni Kart Ekle</h6>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" action="" id="contactCardForm">
                                                <input type="hidden" name="action" value="add_contact_card" id="cardAction">
                                                <input type="hidden" name="card_id" id="cardId">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Başlık *</label>
                                                    <input type="text" class="form-control" name="card_title" id="cardTitle" required maxlength="255">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Açıklama *</label>
                                                    <textarea class="form-control" name="card_description" id="cardDescription" rows="3" required></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Icon *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light">
                                                            <i id="cardIconPreview" class="bi bi-phone fs-4"></i>
                                                        </span>
                                                        <input type="text" class="form-control" name="card_icon" id="cardIcon" 
                                                               value="bi bi-phone" required placeholder="bi bi-phone">
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Icon Rengi</label>
                                                    <select class="form-select" name="card_icon_color" id="cardIconColor">
                                                        <option value="text-primary">Mavi</option>
                                                        <option value="text-success">Yeşil</option>
                                                        <option value="text-info">Açık Mavi</option>
                                                        <option value="text-warning">Sarı</option>
                                                        <option value="text-danger">Kırmızı</option>
                                                        <option value="text-secondary">Gri</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">İletişim Bilgisi *</label>
                                                    <input type="text" class="form-control" name="card_contact_info" id="cardContactInfo" 
                                                           placeholder="+90 (533) 924 29 48" required maxlength="255">
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">İletişim Linki</label>
                                                    <input type="text" class="form-control" name="card_contact_link" id="cardContactLink" 
                                                           placeholder="tel:+905339242948" maxlength="500">
                                                    <div class="form-text">tel:, mailto:, https://wa.me gibi</div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-6 mb-3">
                                                        <label class="form-label fw-bold">Buton Metni</label>
                                                        <input type="text" class="form-control" name="card_button_text" id="cardButtonText" 
                                                               placeholder="Hemen Ara" maxlength="100">
                                                    </div>
                                                    <div class="col-6 mb-3">
                                                        <label class="form-label fw-bold">Buton Rengi</label>
                                                        <select class="form-select" name="card_button_color" id="cardButtonColor">
                                                            <option value="btn-outline-primary">Mavi</option>
                                                            <option value="btn-outline-success">Yeşil</option>
                                                            <option value="btn-outline-info">Açık Mavi</option>
                                                            <option value="btn-outline-warning">Sarı</option>
                                                            <option value="btn-outline-danger">Kırmızı</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Kullanılabilirlik Metni</label>
                                                    <input type="text" class="form-control" name="card_availability_text" id="cardAvailabilityText" 
                                                           placeholder="7/24 Aktif" maxlength="255">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Sıra No</label>
                                                    <input type="number" class="form-control" name="card_order_no" id="cardOrderNo" value="1" min="1">
                                                </div>
                                                
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="card_is_active" id="cardIsActive" checked>
                                                    <label class="form-check-label fw-bold">Aktif</label>
                                                </div>
                                                
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-design-primary" id="cardSubmitBtn">
                                                        <i class="bi bi-plus me-2"></i>Kart Ekle
                                                    </button>
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary w-100 mt-2 d-none" id="cardCancelBtn" onclick="resetContactCardForm()">
                                                    İptal
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- OFİS BİLGİLERİ TAB -->
                        <div class="tab-pane fade" id="nav-office">
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="update_office_info">
                                
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="mb-4">
                                            <label for="office_title" class="form-label fw-bold">Ofis Bölümü Başlığı *</label>
                                            <input type="text" class="form-control form-control-lg" id="office_title" name="office_title" 
                                                   value="<?php echo htmlspecialchars($office_info['title'] ?? 'Ofisimizi Ziyaret Edin'); ?>" required maxlength="255">
                                        </div>

                                        <div class="mb-4">
                                            <label for="office_description" class="form-label fw-bold">Açıklama</label>
                                            <textarea class="form-control" id="office_description" name="office_description" rows="3" 
                                                      maxlength="1000"><?php echo htmlspecialchars($office_info['description'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-4">
                                            <label for="office_address" class="form-label fw-bold">Adres *</label>
                                            <textarea class="form-control" id="office_address" name="office_address" rows="3" required 
                                                      maxlength="500"><?php echo htmlspecialchars($office_info['address'] ?? ''); ?></textarea>
                                            <div class="form-text">Her satır yeni bir adres satırı olarak görüntülenir</div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="office_working_hours" class="form-label fw-bold">Çalışma Saatleri *</label>
                                            <textarea class="form-control" id="office_working_hours" name="office_working_hours" rows="3" required 
                                                      maxlength="500"><?php echo htmlspecialchars($office_info['working_hours'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-4">
                                            <label for="office_transportation" class="form-label fw-bold">Ulaşım Bilgileri</label>
                                            <textarea class="form-control" id="office_transportation" name="office_transportation" rows="3" 
                                                      maxlength="500"><?php echo htmlspecialchars($office_info['transportation'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="mb-4">
                                            <label for="office_google_maps_link" class="form-label fw-bold">Google Maps Linki</label>
                                            <input type="url" class="form-control" id="office_google_maps_link" name="office_google_maps_link" 
                                                   value="<?php echo htmlspecialchars($office_info['google_maps_link'] ?? ''); ?>" maxlength="500">
                                            <div class="form-text">Google Maps'teki konum linkinizi buraya yapıştırın</div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <!-- Ayarlar -->
                                        <div class="card shadow-sm mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0"><i class="bi bi-cog me-2"></i>Ayarlar</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="office_is_active" 
                                                           <?php echo (!isset($office_info['is_active']) || $office_info['is_active']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">
                                                        <i class="bi bi-eye text-success me-1"></i>Aktif
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-design-primary btn-lg">
                                                <i class="bi bi-save me-2"></i>Ofis Bilgilerini Kaydet
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- FORM AYARLARI TAB -->
                        <div class="tab-pane fade" id="nav-form">
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="update_form_settings">
                                
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="mb-4">
                                            <label for="form_title" class="form-label fw-bold">Form Başlığı *</label>
                                            <input type="text" class="form-control form-control-lg" id="form_title" name="form_title" 
                                                   value="<?php echo htmlspecialchars($form_settings['form_title'] ?? 'Bize Mesaj Gönderin'); ?>" required maxlength="255">
                                        </div>

                                        <div class="mb-4">
                                            <label for="form_subtitle" class="form-label fw-bold">Form Alt Başlığı</label>
                                            <input type="text" class="form-control" id="form_subtitle" name="form_subtitle" 
                                                   value="<?php echo htmlspecialchars($form_settings['form_subtitle'] ?? ''); ?>" maxlength="500">
                                        </div>

                                        <div class="mb-4">
                                            <label for="form_success_message" class="form-label fw-bold">Başarı Mesajı</label>
                                            <textarea class="form-control" id="form_success_message" name="form_success_message" rows="3" 
                                                      maxlength="500"><?php echo htmlspecialchars($form_settings['success_message'] ?? ''); ?></textarea>
                                            <div class="form-text">Form gönderildikten sonra gösterilecek mesaj</div>
                                        </div>

                                        <!-- Konu Seçenekleri -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Konu Seçenekleri</label>
                                            <div id="subject-options">
                                                <?php 
                                                $subject_options = !empty($form_settings['subject_options']) ? json_decode($form_settings['subject_options'], true) : [];
                                                foreach ($subject_options as $i => $option): 
                                                ?>
                                                    <div class="subject-option-item mb-2">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" name="subject_options[]" 
                                                                   placeholder="Konu seçeneği" value="<?php echo htmlspecialchars($option); ?>">
                                                            <button type="button" class="btn btn-outline-danger remove-subject-option">
                                                                <i class="bi bi-minus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                <?php if (empty($subject_options)): ?>
                                                    <div class="subject-option-item mb-2">
                                                        <div class="input-group">
                                                            <input type="text" class="form-control" name="subject_options[]" placeholder="Konu seçeneği">
                                                            <button type="button" class="btn btn-outline-danger remove-subject-option" style="display:none;">
                                                                <i class="bi bi-minus"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="addSubjectOption">
                                                <i class="bi bi-plus me-1"></i>Seçenek Ekle
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <!-- Form Ayarları -->
                                        <div class="card shadow-sm mb-4">
                                            <div class="card-header">
                                                <h6 class="mb-0"><i class="bi bi-cog me-2"></i>Form Ayarları</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="enable_privacy_checkbox" 
                                                           <?php echo (!isset($form_settings['enable_privacy_checkbox']) || $form_settings['enable_privacy_checkbox']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">
                                                        Gizlilik politikası checkbox'ı göster
                                                    </label>
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="form_is_active" 
                                                           <?php echo (!isset($form_settings['is_active']) || $form_settings['is_active']) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">
                                                        <i class="bi bi-eye text-success me-1"></i>Aktif
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-design-primary btn-lg">
                                                <i class="bi bi-save me-2"></i>Form Ayarlarını Kaydet
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- MESAJLAR TAB -->
                        <div class="tab-pane fade" id="nav-messages">
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="mb-0"><i class="bi bi-comments me-2 text-primary"></i>Son İletişim Mesajları</h5>
                                        <a href="../admin/contact-messages.php" class="btn btn-outline-primary">
                                            <i class="bi bi-list me-2"></i>Tüm Mesajları Gör
                                        </a>
                                    </div>
                                    
                                    <?php if (!empty($recent_messages)): ?>
                                        <div class="row">
                                            <?php foreach ($recent_messages as $msg): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="card h-100">
                                                        <div class="card-header py-2">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <h6 class="mb-0"><?php echo htmlspecialchars($msg['subject']); ?></h6>
                                                                <span class="badge bg-<?php echo $msg['status'] === 'new' ? 'danger' : ($msg['status'] === 'read' ? 'warning' : 'success'); ?>">
                                                                    <?php echo ucfirst($msg['status']); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="card-body py-2">
                                                            <div class="d-flex align-items-center mb-2">
                                                                <strong class="me-2"><?php echo htmlspecialchars($msg['name']); ?></strong>
                                                                <small class="text-muted"><?php echo htmlspecialchars($msg['email']); ?></small>
                                                            </div>
                                                            <p class="small mb-2">
                                                                <?php echo nl2br(htmlspecialchars(substr($msg['message'], 0, 100))); ?>
                                                                <?php if (strlen($msg['message']) > 100): ?>...<?php endif; ?>
                                                            </p>
                                                            <small class="text-muted">
                                                                <i class="bi bi-clock me-1"></i>
                                                                <?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted py-5">
                                            <i class="bi bi-inbox fa-3x mb-3 opacity-25"></i>
                                            <p>Henüz mesaj bulunmuyor.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Icon önizleme
document.getElementById('cardIcon').addEventListener('input', function() {
    document.getElementById('cardIconPreview').className = this.value.trim() || 'bi bi-phone fs-4';
});

// Konu seçeneği ekleme/silme
document.getElementById('addSubjectOption').addEventListener('click', function() {
    const container = document.getElementById('subject-options');
    const item = document.createElement('div');
    item.className = 'subject-option-item mb-2';
    item.innerHTML = `
        <div class="input-group">
            <input type="text" class="form-control" name="subject_options[]" placeholder="Konu seçeneği">
            <button type="button" class="btn btn-outline-danger remove-subject-option">
                <i class="bi bi-minus"></i>
            </button>
        </div>
    `;
    container.appendChild(item);
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-subject-option')) {
        const container = document.getElementById('subject-options');
        if (container.children.length > 1) {
            e.target.closest('.subject-option-item').remove();
        }
    }
});

// Contact Card yönetimi
function editContactCard(card) {
    document.getElementById('cardAction').value = 'update_contact_card';
    document.getElementById('cardId').value = card.id;
    document.getElementById('cardTitle').value = card.title;
    document.getElementById('cardDescription').value = card.description;
    document.getElementById('cardIcon').value = card.icon;
    document.getElementById('cardIconColor').value = card.icon_color;
    document.getElementById('cardContactInfo').value = card.contact_info;
    document.getElementById('cardContactLink').value = card.contact_link || '';
    document.getElementById('cardButtonText').value = card.button_text || '';
    document.getElementById('cardButtonColor').value = card.button_color;
    document.getElementById('cardAvailabilityText').value = card.availability_text || '';
    document.getElementById('cardOrderNo').value = card.order_no;
    document.getElementById('cardIsActive').checked = card.is_active == 1;
    
    // Icon önizlemesini güncelle
    document.getElementById('cardIconPreview').className = card.icon + ' fs-4';
    
    document.getElementById('cardSubmitBtn').innerHTML = '<i class="bi bi-save me-2"></i>Güncelle';
    document.getElementById('cardCancelBtn').classList.remove('d-none');
    
    // Cards tab'ına git
    const cardsTab = new bootstrap.Tab(document.getElementById('nav-cards-tab'));
    cardsTab.show();
}

function resetContactCardForm() {
    document.getElementById('contactCardForm').reset();
    document.getElementById('cardAction').value = 'add_contact_card';
    document.getElementById('cardId').value = '';
    document.getElementById('cardIcon').value = 'bi bi-phone';
    document.getElementById('cardIconPreview').className = 'bi bi-phone fs-4';
    document.getElementById('cardSubmitBtn').innerHTML = '<i class="bi bi-plus me-2"></i>Kart Ekle';
    document.getElementById('cardCancelBtn').classList.add('d-none');
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
</script>

<?php include '../includes/design_footer.php'; ?>