<?php
/**
 * Design Panel - Site Ayarları
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Sayfa bilgileri
$pageTitle = 'Site Ayarları';
$pageDescription = 'Site tasarım ayarlarını düzenleyin';
$breadcrumbs = [
    ['title' => 'Design Panel', 'url' => 'index.php'],
    ['title' => 'Site Ayarları']
];

// Form işlemleri
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // AJAX isteği kontrolü - daha güvenilir yöntem
        $isAjax = (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) || (
            isset($_SERVER['CONTENT_TYPE']) && 
            strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false &&
            isset($_POST['save_settings'])
        );
        
        if (isset($_POST['save_settings'])) {
            $updatedCount = 0;
            $errorCount = 0;
            
            foreach ($_POST as $key => $value) {
                if ($key !== 'save_settings') {
                    try {
                        // String değeri olarak sakla
                        $value = (string) $value;
                        
                        // Mevcut ayar var mı kontrol et
                        $checkStmt = $pdo->prepare("SELECT id FROM design_settings WHERE setting_key = ?");
                        $checkStmt->execute([$key]);
                        $existing = $checkStmt->fetch();
                        
                        if ($existing) {
                            // Güncelle
                            $stmt = $pdo->prepare("UPDATE design_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                            $result = $stmt->execute([$value, $key]);
                            if ($result) $updatedCount++;
                        } else {
                            // Yeni ekle
                            $id = sprintf(
                                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                                mt_rand(0, 0xffff),
                                mt_rand(0, 0x0fff) | 0x4000,
                                mt_rand(0, 0x3fff) | 0x8000,
                                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                            );
                            
                            $stmt = $pdo->prepare("INSERT INTO design_settings (id, setting_key, setting_value, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                            $result = $stmt->execute([$id, $key, $value]);
                            if ($result) $updatedCount++;
                        }
                    } catch (PDOException $e) {
                        error_log("Settings update error for $key: " . $e->getMessage());
                        $errorCount++;
                    }
                }
            }
            
            // Checkbox'lar için özel işlem (işaretli değilse POST'da gelmez)
            $checkboxFields = [
                'site_maintenance_mode',
                'hero_typewriter_enable'
            ];
            
            foreach ($checkboxFields as $checkboxField) {
                if (!isset($_POST[$checkboxField])) {
                    try {
                        // Checkbox işaretli değil, 0 olarak kaydet
                        $checkStmt = $pdo->prepare("SELECT id FROM design_settings WHERE setting_key = ?");
                        $checkStmt->execute([$checkboxField]);
                        $existing = $checkStmt->fetch();
                        
                        if ($existing) {
                            $stmt = $pdo->prepare("UPDATE design_settings SET setting_value = '0', updated_at = NOW() WHERE setting_key = ?");
                            $result = $stmt->execute([$checkboxField]);
                            if ($result) $updatedCount++;
                        } else {
                            $id = sprintf(
                                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                                mt_rand(0, 0xffff),
                                mt_rand(0, 0x0fff) | 0x4000,
                                mt_rand(0, 0x3fff) | 0x8000,
                                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                            );
                            
                            $stmt = $pdo->prepare("INSERT INTO design_settings (id, setting_key, setting_value, created_at, updated_at) VALUES (?, ?, '0', NOW(), NOW())");
                            $result = $stmt->execute([$id, $checkboxField]);
                            if ($result) $updatedCount++;
                        }
                    } catch (PDOException $e) {
                        error_log("Checkbox update error for $checkboxField: " . $e->getMessage());
                        $errorCount++;
                    }
                }
            }
            
            // Sonuç mesajı
            if ($errorCount === 0) {
                $message = "Ayarlar başarıyla kaydedildi. ($updatedCount ayar güncellendi)";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $message]);
                    exit;
                } else {
                    header('Location: settings.php?success=' . urlencode($message));
                    exit;
                }
            } else {
                $message = "Bazı ayarlar kaydedilemedi. ($errorCount hata, $updatedCount başarılı)";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $message]);
                    exit;
                } else {
                    $error = $message;
                }
            }
        }
    } catch (Exception $e) {
        error_log('Settings save error: ' . $e->getMessage());
        $message = 'Ayarlar kaydedilirken bir hata oluştu: ' . $e->getMessage();
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        } else {
            $error = $message;
        }
    }
}

// Mevcut ayarları al
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value, description FROM design_settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    $settings = [];
    $error = "Ayarlar yüklenemedi: " . $e->getMessage();
}

// Default değerler
$defaults = [
    'site_theme_color' => '#667eea',
    'site_secondary_color' => '#764ba2',
    'site_accent_color' => '#e91c1c',
    'site_success_color' => '#27ae60',
    'site_warning_color' => '#f39c12',
    'site_danger_color' => '#e74c3c',
    'hero_typewriter_enable' => '1',
    'hero_typewriter_words' => 'Optimize Edin,Güçlendirin,Geliştirin',
    'hero_animation_speed' => '5000',
    'site_logo_text' => 'Mr ECU',
    'site_tagline' => 'Profesyonel ECU Hizmetleri',
    'footer_text' => 'Tüm hakları saklıdır.',
    'contact_phone' => '+90 (555) 123 45 67',
    'contact_address' => 'İstanbul, Türkiye',
    'social_facebook' => '',
    'social_twitter' => '',
    'social_instagram' => '',
    'social_linkedin' => '',
    'site_maintenance_mode' => '0',
    'site_analytics_code' => '',
    'custom_css' => '',
    'custom_js' => '',
    'terms_of_service_title' => 'Kullanım Şartları',
    'terms_of_service_content' => '',
    'privacy_policy_title' => 'Gizlilik Politikası',
    'privacy_policy_content' => ''
];

// Ayarları default değerlerle birleştir
foreach ($defaults as $key => $defaultValue) {
    if (!isset($settings[$key])) {
        $settings[$key] = $defaultValue;
    }
}

// Header include
include '../includes/design_header.php';
?>

<!-- Site Ayarları -->
<form method="POST" id="settingsForm">
    <div class="row g-4">
        <!-- Genel Ayarlar -->
        <div class="col-lg-6">
            <div class="design-card h-100">
                <div class="design-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-gear me-2"></i>Genel Ayarlar
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="site_logo_text" class="form-label">Site Logo Metni</label>
                        <input type="text" class="form-control" id="site_logo_text" name="site_logo_text" 
                               value="<?php echo htmlspecialchars($settings['site_logo_text']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_tagline" class="form-label">Site Sloganı</label>
                        <input type="text" class="form-control" id="site_tagline" name="site_tagline" 
                               value="<?php echo htmlspecialchars($settings['site_tagline']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="footer_text" class="form-label">Footer Metni</label>
                        <input type="text" class="form-control" id="footer_text" name="footer_text" 
                               value="<?php echo htmlspecialchars($settings['footer_text']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="site_maintenance_mode" name="site_maintenance_mode" 
                                   value="1" <?php echo $settings['site_maintenance_mode'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="site_maintenance_mode">
                                Bakım Modu
                            </label>
                            <small class="form-text text-muted d-block">Aktif olduğunda site ziyaretçilere kapalı olur</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Renk Ayarları -->
        <div class="col-lg-6">
            <div class="design-card h-100">
                <div class="design-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-palette me-2"></i>Renk Şeması
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="site_theme_color" class="form-label">Ana Tema Rengi</label>
                            <div class="d-flex align-items-center">
                                <input type="color" class="form-control form-control-color" id="site_theme_color" 
                                       name="site_theme_color" value="<?php echo htmlspecialchars($settings['site_theme_color']); ?>">
                                <div class="color-preview ms-2" data-preview="site_theme_color"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="site_secondary_color" class="form-label">İkincil Renk</label>
                            <div class="d-flex align-items-center">
                                <input type="color" class="form-control form-control-color" id="site_secondary_color" 
                                       name="site_secondary_color" value="<?php echo htmlspecialchars($settings['site_secondary_color']); ?>">
                                <div class="color-preview ms-2" data-preview="site_secondary_color"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="site_accent_color" class="form-label">Vurgu Rengi</label>
                            <div class="d-flex align-items-center">
                                <input type="color" class="form-control form-control-color" id="site_accent_color" 
                                       name="site_accent_color" value="<?php echo htmlspecialchars($settings['site_accent_color']); ?>">
                                <div class="color-preview ms-2" data-preview="site_accent_color"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="site_success_color" class="form-label">Başarı Rengi</label>
                            <div class="d-flex align-items-center">
                                <input type="color" class="form-control form-control-color" id="site_success_color" 
                                       name="site_success_color" value="<?php echo htmlspecialchars($settings['site_success_color']); ?>">
                                <div class="color-preview ms-2" data-preview="site_success_color"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="site_warning_color" class="form-label">Uyarı Rengi</label>
                            <div class="d-flex align-items-center">
                                <input type="color" class="form-control form-control-color" id="site_warning_color" 
                                       name="site_warning_color" value="<?php echo htmlspecialchars($settings['site_warning_color']); ?>">
                                <div class="color-preview ms-2" data-preview="site_warning_color"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="site_danger_color" class="form-label">Hata Rengi</label>
                            <div class="d-flex align-items-center">
                                <input type="color" class="form-control form-control-color" id="site_danger_color" 
                                       name="site_danger_color" value="<?php echo htmlspecialchars($settings['site_danger_color']); ?>">
                                <div class="color-preview ms-2" data-preview="site_danger_color"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hero Ayarları -->
        <div class="col-lg-6">
            <div class="design-card h-100">
                <div class="design-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-magic me-2"></i>Hero Bölümü Ayarları
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="hero_typewriter_enable" name="hero_typewriter_enable" 
                                   value="1" <?php echo $settings['hero_typewriter_enable'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="hero_typewriter_enable">
                                Typewriter Efektini Etkinleştir
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="hero_typewriter_words" class="form-label">Typewriter Kelimeleri</label>
                        <input type="text" class="form-control" id="hero_typewriter_words" name="hero_typewriter_words" 
                               value="<?php echo htmlspecialchars($settings['hero_typewriter_words']); ?>">
                        <small class="form-text text-muted">Virgülle ayırarak yazın (örn: Kelime1,Kelime2,Kelime3)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="hero_animation_speed" class="form-label">Slider Hızı (ms)</label>
                        <input type="number" class="form-control" id="hero_animation_speed" name="hero_animation_speed" 
                               value="<?php echo htmlspecialchars($settings['hero_animation_speed']); ?>" min="1000" max="10000" step="500">
                        <small class="form-text text-muted">Slider'lar arası geçiş süresi (milisaniye)</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- İletişim Bilgileri -->
        <div class="col-lg-6">
            <div class="design-card h-100">
                <div class="design-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-address-book me-2"></i>İletişim Bilgileri
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Telefon</label>
                        <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                               value="<?php echo htmlspecialchars($settings['contact_phone']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_address" class="form-label">Adres</label>
                        <textarea class="form-control" id="contact_address" name="contact_address" rows="2"><?php echo htmlspecialchars($settings['contact_address']); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sosyal Medya -->
        <div class="col-lg-6">
            <div class="design-card h-100">
                <div class="design-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-facebook me-2"></i>Sosyal Medya
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="social_facebook" class="form-label">Facebook URL</label>
                        <input type="url" class="form-control" id="social_facebook" name="social_facebook" 
                               value="<?php echo htmlspecialchars($settings['social_facebook']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="social_twitter" class="form-label">Twitter URL</label>
                        <input type="url" class="form-control" id="social_twitter" name="social_twitter" 
                               value="<?php echo htmlspecialchars($settings['social_twitter']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="social_instagram" class="form-label">Instagram URL</label>
                        <input type="url" class="form-control" id="social_instagram" name="social_instagram" 
                               value="<?php echo htmlspecialchars($settings['social_instagram']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="social_linkedin" class="form-label">LinkedIn URL</label>
                        <input type="url" class="form-control" id="social_linkedin" name="social_linkedin" 
                               value="<?php echo htmlspecialchars($settings['social_linkedin']); ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Analytics & Tracking -->
        <div class="col-lg-6">
            <div class="design-card h-100">
                <div class="design-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart me-2"></i>Analytics & Tracking
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="site_analytics_code" class="form-label">Google Analytics Kodu</label>
                        <textarea class="form-control" id="site_analytics_code" name="site_analytics_code" rows="3" 
                                  placeholder="Google Analytics tracking kodu buraya..."><?php echo htmlspecialchars($settings['site_analytics_code']); ?></textarea>
                        <small class="form-text text-muted">Google Analytics veya diğer tracking kodlarını buraya ekleyin</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Kullanım Şartları ve Gizlilik Politikası -->
        <div class="col-12">
            <div class="design-card">
                <div class="design-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-check me-2"></i>Kullanım Şartları ve Gizlilik Politikası
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Kullanım Şartları -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="terms_of_service_title" class="form-label">Kullanım Şartları Başlığı</label>
                                <input type="text" class="form-control" id="terms_of_service_title" name="terms_of_service_title" 
                                       value="<?php echo htmlspecialchars($settings['terms_of_service_title']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="terms_of_service_content" class="form-label">Kullanım Şartları İçeriği</label>
                                <textarea class="form-control" id="terms_of_service_content" name="terms_of_service_content" rows="15" 
                                          placeholder="Kullanım şartlarınızı buraya yazın..."><?php echo htmlspecialchars($settings['terms_of_service_content']); ?></textarea>
                                <small class="form-text text-muted">HTML etiketleri kullanabilirsiniz</small>
                            </div>
                        </div>
                        
                        <!-- Gizlilik Politikası -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="privacy_policy_title" class="form-label">Gizlilik Politikası Başlığı</label>
                                <input type="text" class="form-control" id="privacy_policy_title" name="privacy_policy_title" 
                                       value="<?php echo htmlspecialchars($settings['privacy_policy_title']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="privacy_policy_content" class="form-label">Gizlilik Politikası İçeriği</label>
                                <textarea class="form-control" id="privacy_policy_content" name="privacy_policy_content" rows="15" 
                                          placeholder="Gizlilik politikanızı buraya yazın..."><?php echo htmlspecialchars($settings['privacy_policy_content']); ?></textarea>
                                <small class="form-text text-muted">HTML etiketleri kullanabilirsiniz</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Önizleme Butonları -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="previewTerms()">
                                    <i class="bi bi-eye me-1"></i>Kullanım Şartları Önizleme
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="previewPrivacy()">
                                    <i class="bi bi-eye me-1"></i>Gizlilik Politikası Önizleme
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Özel CSS & JS -->
        <div class="col-12">
            <div class="design-card">
                <div class="design-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-code me-2"></i>Özel Kod
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="custom_css" class="form-label">Özel CSS</label>
                                <textarea class="form-control" id="custom_css" name="custom_css" rows="8" 
                                          placeholder="/* Özel CSS kodlarınızı buraya yazın */"><?php echo htmlspecialchars($settings['custom_css']); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="custom_js" class="form-label">Özel JavaScript</label>
                                <textarea class="form-control" id="custom_js" name="custom_js" rows="8" 
                                          placeholder="// Özel JavaScript kodlarınızı buraya yazın"><?php echo htmlspecialchars($settings['custom_js']); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Kaydet Butonu -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-end gap-3">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Geri Dön
                </a>
                <button type="submit" name="save_settings" class="btn btn-design-primary" data-original-text="Ayarları Kaydet">
                    <i class="bi bi-save me-2"></i>Ayarları Kaydet
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Live Preview -->
<div class="row mt-4">
    <div class="col-12">
        <div class="design-card">
            <div class="design-card-header">
                <h5 class="mb-0">
                    <i class="bi bi-eye me-2"></i>Canlı Önizleme
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Değişikliklerinizi görmek için <a href="../index.php" target="_blank">ana sayfayı</a> yeni sekmede açın.
                    Değişiklikler anında yansıtılacaktır.
                </div>
                
                <!-- Renk Önizlemesi -->
                <div class="color-palette">
                    <h6>Renk Paleti Önizlemesi:</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <div class="color-sample" data-color="site_theme_color">
                            <div class="color-box" style="background-color: <?php echo $settings['site_theme_color']; ?>"></div>
                            <small>Ana Renk</small>
                        </div>
                        <div class="color-sample" data-color="site_secondary_color">
                            <div class="color-box" style="background-color: <?php echo $settings['site_secondary_color']; ?>"></div>
                            <small>İkincil</small>
                        </div>
                        <div class="color-sample" data-color="site_accent_color">
                            <div class="color-box" style="background-color: <?php echo $settings['site_accent_color']; ?>"></div>
                            <small>Vurgu</small>
                        </div>
                        <div class="color-sample" data-color="site_success_color">
                            <div class="color-box" style="background-color: <?php echo $settings['site_success_color']; ?>"></div>
                            <small>Başarı</small>
                        </div>
                        <div class="color-sample" data-color="site_warning_color">
                            <div class="color-box" style="background-color: <?php echo $settings['site_warning_color']; ?>"></div>
                            <small>Uyarı</small>
                        </div>
                        <div class="color-sample" data-color="site_danger_color">
                            <div class="color-box" style="background-color: <?php echo $settings['site_danger_color']; ?>"></div>
                            <small>Hata</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.color-sample {
    text-align: center;
}

.color-box {
    width: 60px;
    height: 40px;
    border-radius: 8px;
    border: 2px solid #ddd;
    margin-bottom: 5px;
    transition: all 0.3s ease;
}

.color-sample:hover .color-box {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.color-palette {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.form-control-color {
    width: 60px;
    height: 40px;
    border-radius: 8px;
}

#custom_css, #custom_js {
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
}
</style>

<script>
// Color preview update
document.querySelectorAll('input[type="color"]').forEach(input => {
    input.addEventListener('change', function() {
        // Update preview
        const preview = document.querySelector(`[data-preview="${this.name}"]`);
        if (preview) {
            preview.style.backgroundColor = this.value;
        }
        
        // Update color palette
        const colorSample = document.querySelector(`[data-color="${this.name}"] .color-box`);
        if (colorSample) {
            colorSample.style.backgroundColor = this.value;
        }
    });
});

// Auto-save (every 30 seconds)
let autoSaveTimer;
function scheduleAutoSave() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        const formData = new FormData(document.getElementById('settingsForm'));
        formData.append('save_settings', '1');
        
        autoSave(formData, 'settings.php');
    }, 30000);
}

// Reset auto-save timer on form changes
document.getElementById('settingsForm').addEventListener('change', scheduleAutoSave);
document.getElementById('settingsForm').addEventListener('input', scheduleAutoSave);

// Form submission
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // jQuery'nin hazır olmasını bekle
    window.waitForjQuery(function() {
        // Form verilerini topla
        const formData = new FormData(document.getElementById('settingsForm'));
        formData.append('save_settings', '1');
        
        // Submit butonunu bul ve loading state'e al
        const submitBtn = document.getElementById('settingsForm').querySelector('[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // AJAX ile kaydet (jQuery veya fallback kullan)
        const ajaxOptions = {
            url: 'settings.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            beforeSend: function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-spinner-border spinner-border-sm me-2"></i>Kaydediliyor...';
            },
            success: function(response) {
                showToast('Ayarlar başarıyla kaydedildi', 'success');
                
                // Sayfa yeniden yükle (sadece settings.php'ye yönlendir)
                setTimeout(() => {
                    window.location.href = 'settings.php';
                }, 1000);
            },
            error: function(xhr) {
                let errorMsg = 'Kaydetme sırasında bir hata oluştu';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    // HTML response'dan hata mesajını çıkarmaya çalış
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(xhr.responseText, 'text/html');
                    const errorAlert = doc.querySelector('.alert-danger');
                    if (errorAlert) {
                        errorMsg = errorAlert.textContent.trim();
                    }
                }
                showToast(errorMsg, 'error');
            },
            complete: function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        };
        
        // jQuery varsa kulllan, yoksa fallback AJAX kullan
        if (typeof $ !== 'undefined' && typeof $.ajax === 'function') {
            $.ajax(ajaxOptions);
        } else {
            // Fallback AJAX çağrısı (window.$ fallback fonksiyonu)
            window.$.ajax(ajaxOptions);
        }
    }, 10000); // 10 saniye timeout
});

// Initialize color previews
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[type="color"]').forEach(input => {
        const preview = document.querySelector(`[data-preview="${input.name}"]`);
        if (preview) {
            preview.style.backgroundColor = input.value;
        }
    });
});

// Kullanım Şartları önizleme
function previewTerms() {
    const title = document.getElementById('terms_of_service_title').value;
    const content = document.getElementById('terms_of_service_content').value;
    
    const previewWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
    previewWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${title}</title>
            <meta charset="utf-8">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
                .container { margin-top: 2rem; }
                h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>${title}</h1>
                <div class="content">${content}</div>
            </div>
        </body>
        </html>
    `);
    previewWindow.document.close();
}

// Gizlilik politikası önizleme
function previewPrivacy() {
    const title = document.getElementById('privacy_policy_title').value;
    const content = document.getElementById('privacy_policy_content').value;
    
    const previewWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
    previewWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${title}</title>
            <meta charset="utf-8">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
                .container { margin-top: 2rem; }
                h1 { color: #333; border-bottom: 3px solid #28a745; padding-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>${title}</h1>
                <div class="content">${content}</div>
            </div>
        </body>
        </html>
    `);
    previewWindow.document.close();
}
</script>

<?php
// Footer include
include '../includes/design_footer.php';
?>
