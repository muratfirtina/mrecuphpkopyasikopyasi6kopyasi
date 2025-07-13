<?php
/**
 * Mr ECU - Modern Dosya Yükleme Sayfası (GUID System)
 */

// Debug ve error handling
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ajax ile model getirme - SAYFA YÜKLENMESINDEN ÖNCE KONTROL ET
if (isset($_GET['get_models']) && isset($_GET['brand_id'])) {
    // AJAX request - sayfa render etme
    try {
        require_once '../config/config.php';
        require_once '../config/database.php';
        
        // AJAX response için tüm output'u temizle
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json');
        
        // Giriş kontrolü
        if (!isLoggedIn()) {
            echo json_encode(['error' => 'Giriş gerekli']);
            exit;
        }
        
        // FileManager'i başlat
        $fileManager = new FileManager($pdo);
        
        $brandId = sanitize($_GET['brand_id']);
        
        // GUID format kontrolü
        if (!isValidUUID($brandId)) {
            echo json_encode(['error' => 'Geçersiz marka ID formatı']);
            exit;
        }
        
        $models = $fileManager->getModelsByBrand($brandId);
        echo json_encode($models);
        
    } catch (Exception $e) {
        error_log('AJAX Model Error: ' . $e->getMessage());
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode(['error' => 'Server hatası oluştu']);
    }
    exit; // AJAX request bitir
}

// Normal sayfa yükleme devam eder
try {
    require_once '../config/config.php';
    require_once '../config/database.php';
    
    // Database bağlantısı kontrolü
    if (!isset($pdo) || $pdo === null) {
        throw new Exception('Database bağlantısı kurulamadı!');
    }
    
} catch (Exception $e) {
    error_log('Upload page init error: ' . $e->getMessage());
    echo "<div class='alert alert-danger'>Sistem hatası: " . $e->getMessage() . "</div>";
    echo "<p><a href='../login.php'>Giriş sayfasına dön</a></p>";
    exit;
}

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/upload.php');
}

// Sınıfları başlat
try {
    $user = new User($pdo);
    $fileManager = new FileManager($pdo);
} catch (Exception $e) {
    error_log('Class initialization error: ' . $e->getMessage());
    echo "<div class='alert alert-danger'>Sınıf başlatma hatası: " . $e->getMessage() . "</div>";
    exit;
}

// Session'daki kredi bilgisini güncelle
try {
    $_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);
    echo '<!-- DEBUG: User credits güncellendi: ' . $_SESSION['credits'] . ' -->';
} catch (Exception $e) {
    echo '<!-- DEBUG: Credits güncelleme hatası: ' . $e->getMessage() . ' -->';
    $_SESSION['credits'] = 0;
}

$error = '';
$success = '';

// Araç markalarını getir
try {
    $brands = $fileManager->getBrands();
    echo '<!-- DEBUG: Markalar getirildi, adet: ' . count($brands) . ' -->';
} catch (Exception $e) {
    echo '<!-- DEBUG: Marka getirme hatası: ' . $e->getMessage() . ' -->';
    $brands = [];
}

// Dosya yükleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $brandId = sanitize($_POST['brand_id']);
    $modelId = sanitize($_POST['model_id']);
    
    // GUID format kontrolleri
    if (!isValidUUID($brandId)) {
        $error = 'Geçersiz marka ID formatı.';
    } elseif (!isValidUUID($modelId)) {
        $error = 'Geçersiz model ID formatı.';
    } else {
        $vehicleData = [
            'brand_id' => $brandId,
            'model_id' => $modelId,
            'year' => (int)$_POST['year'],
            'plate' => !empty($_POST['plate']) ? strtoupper(sanitize($_POST['plate'])) : null,
            'ecu_type' => sanitize($_POST['ecu_type']),
            'engine_code' => sanitize($_POST['engine_code']),
            'gearbox_type' => sanitize($_POST['gearbox_type']),
            'fuel_type' => sanitize($_POST['fuel_type']),
            'hp_power' => !empty($_POST['hp_power']) ? (int)$_POST['hp_power'] : null,
            'nm_torque' => !empty($_POST['nm_torque']) ? (int)$_POST['nm_torque'] : null
        ];
        
        $notes = sanitize($_POST['notes']); // Form'dan notes gelir ama veritabanında upload_notes olarak saklanır
        
        // Validation
        if ($vehicleData['year'] < 1990 || $vehicleData['year'] > date('Y') + 1) {
            $error = 'Geçerli bir model yılı girin.';
        } else {
            // Debug: session user_id kontrol
            error_log('Upload attempt - User ID: ' . $_SESSION['user_id'] . ', Type: ' . gettype($_SESSION['user_id']));
            error_log('Vehicle data: ' . print_r($vehicleData, true));
            
            $result = $fileManager->uploadFile($_SESSION['user_id'], $_FILES['file'], $vehicleData, $notes);
            
            if ($result['success']) {
                $success = $result['message'];
                // Form verilerini temizle
                $_POST = [];
            } else {
                $error = $result['message'];
                // Debug: hata detayı
                error_log('Upload error: ' . $result['message']);
            }
        }
    }
}

$pageTitle = 'Dosya Yükle';

echo '<!-- DEBUG: Header yüklenmeye hazırlanıyor -->';

// Header include
try {
    include '../includes/user_header.php';
    echo '<!-- DEBUG: Header başarıyla yüklendi -->';
} catch (Exception $e) {
    die('Header yükleme hatası: ' . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include '_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="fas fa-upload me-2 text-primary"></i>Dosya Yükle
                    </h1>
                    <p class="text-muted mb-0">ECU dosyanızı güvenli bir şekilde yükleyin ve işletmemize gönderin</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="files.php" class="btn btn-outline-secondary">
                            <i class="fas fa-folder me-1"></i>Dosyalarım
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-modern" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                        <div>
                            <strong>Hata!</strong> <?php echo $error; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-modern" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-3 fa-lg"></i>
                        <div>
                            <strong>Başarılı!</strong> <?php echo $success; ?>
                            <div class="mt-2">
                                <a href="files.php" class="btn btn-success btn-sm">
                                    <i class="fas fa-folder me-1"></i>Dosyalarımı Görüntüle
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="upload-wizard">
                        <!-- Adım Göstergesi -->
                        <div class="wizard-steps mb-4">
                            <div class="step active" data-step="1">
                                <div class="step-number">1</div>
                                <div class="step-title">Araç Bilgileri</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step" data-step="2">
                                <div class="step-number">2</div>
                                <div class="step-title">Dosya Yükleme</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step" data-step="3">
                                <div class="step-number">3</div>
                                <div class="step-title">Tamamla</div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <form method="POST" enctype="multipart/form-data" class="upload-form needs-validation" novalidate>
                                    <!-- Adım 1: Araç Bilgileri -->
                                    <div class="form-step active" id="step-1">
                                        <div class="step-header mb-4">
                                            <h4 class="mb-2">
                                                <i class="fas fa-car me-2 text-primary"></i>Araç Bilgileri
                                            </h4>
                                            <p class="text-muted">ECU'nun bulunduğu aracın teknik bilgilerini girin</p>
                                        </div>
                                        
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label for="brand_id" class="form-label fw-semibold">Marka *</label>
                                                <select class="form-select form-control-modern" id="brand_id" name="brand_id" required>
                                                    <option value="">Marka Seçin</option>
                                                    <?php foreach ($brands as $brand): ?>
                                                        <option value="<?php echo htmlspecialchars($brand['id']); ?>" 
                                                                data-guid="<?php echo htmlspecialchars($brand['id']); ?>"
                                                                <?php echo (isset($_POST['brand_id']) && $_POST['brand_id'] == $brand['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($brand['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="invalid-feedback">Marka seçimi zorunludur.</div>
                                                <div class="form-help">
                                                    <small class="text-muted">
                                                        <i class="fas fa-shield-alt me-1"></i>GUID ID sistemi kullanılıyor
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <label for="model_id" class="form-label fw-semibold">Model *</label>
                                                <select class="form-select form-control-modern" id="model_id" name="model_id" required disabled>
                                                    <option value="">Önce marka seçin</option>
                                                </select>
                                                <div class="invalid-feedback">Model seçimi zorunludur.</div>
                                                <div class="form-help">
                                                    <small class="text-muted">
                                                        <i class="fas fa-shield-alt me-1"></i>GUID ID sistemi kullanılıyor
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <label for="year" class="form-label fw-semibold">Model Yılı *</label>
                                                <input type="number" class="form-control form-control-modern" id="year" name="year" 
                                                       min="1990" max="<?php echo date('Y') + 1; ?>" 
                                                       value="<?php echo isset($_POST['year']) ? $_POST['year'] : ''; ?>" 
                                                       placeholder="<?php echo date('Y'); ?>" required>
                                                <div class="invalid-feedback">Geçerli bir model yılı girin.</div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <label for="plate" class="form-label fw-semibold">Plaka</label>
                                                <input type="text" class="form-control form-control-modern" id="plate" name="plate" 
                                                       placeholder="34 ABC 123" 
                                                       value="<?php echo isset($_POST['plate']) ? htmlspecialchars($_POST['plate']) : ''; ?>" 
                                                       style="text-transform: uppercase;">
                                                <div class="form-help">
                                                    <small class="text-muted">Araç plaka numarası</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-6">
                                                <label for="ecu_type" class="form-label fw-semibold">ECU Tipi</label>
                                                <input type="text" class="form-control form-control-modern" id="ecu_type" name="ecu_type" 
                                                       placeholder="Örn: Bosch ME7.5, Siemens PCR2.1" 
                                                       value="<?php echo isset($_POST['ecu_type']) ? htmlspecialchars($_POST['ecu_type']) : ''; ?>">
                                                <div class="form-help">
                                                    <small class="text-muted">ECU üzerindeki model bilgisini girin</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="engine_code" class="form-label fw-semibold">Motor Kodu</label>
                                                <input type="text" class="form-control form-control-modern" id="engine_code" name="engine_code" 
                                                       placeholder="Örn: AWT, AWP, BKD" 
                                                       value="<?php echo isset($_POST['engine_code']) ? htmlspecialchars($_POST['engine_code']) : ''; ?>">
                                                <div class="form-help">
                                                    <small class="text-muted">Motor bloğundaki kod veya ruhsat bilgisi</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-3 mt-2">
                                            <div class="col-md-3">
                                                <label for="gearbox_type" class="form-label fw-semibold">Şanzıman</label>
                                                <select class="form-select form-control-modern" id="gearbox_type" name="gearbox_type">
                                                    <option value="Manual" <?php echo (isset($_POST['gearbox_type']) && $_POST['gearbox_type'] === 'Manual') ? 'selected' : ''; ?>>Manuel</option>
                                                    <option value="Automatic" <?php echo (isset($_POST['gearbox_type']) && $_POST['gearbox_type'] === 'Automatic') ? 'selected' : ''; ?>>Otomatik</option>
                                                    <option value="CVT" <?php echo (isset($_POST['gearbox_type']) && $_POST['gearbox_type'] === 'CVT') ? 'selected' : ''; ?>>CVT</option>
                                                    <option value="DSG" <?php echo (isset($_POST['gearbox_type']) && $_POST['gearbox_type'] === 'DSG') ? 'selected' : ''; ?>>DSG</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <label for="fuel_type" class="form-label fw-semibold">Yakıt Türü</label>
                                                <select class="form-select form-control-modern" id="fuel_type" name="fuel_type">
                                                    <option value="Benzin" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type'] === 'Benzin') ? 'selected' : ''; ?>>Benzin</option>
                                                    <option value="Dizel" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type'] === 'Dizel') ? 'selected' : ''; ?>>Dizel</option>
                                                    <option value="LPG" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type'] === 'LPG') ? 'selected' : ''; ?>>LPG</option>
                                                    <option value="Hybrid" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type'] === 'Hybrid') ? 'selected' : ''; ?>>Hibrit</option>
                                                    <option value="Electric" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type'] === 'Electric') ? 'selected' : ''; ?>>Elektrik</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <label for="hp_power" class="form-label fw-semibold">Güç (HP)</label>
                                                <input type="number" class="form-control form-control-modern" id="hp_power" name="hp_power" 
                                                       min="1" max="2000" placeholder="150" 
                                                       value="<?php echo isset($_POST['hp_power']) ? $_POST['hp_power'] : ''; ?>">
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <label for="nm_torque" class="form-label fw-semibold">Tork (Nm)</label>
                                                <input type="number" class="form-control form-control-modern" id="nm_torque" name="nm_torque" 
                                                       min="1" max="5000" placeholder="320" 
                                                       value="<?php echo isset($_POST['nm_torque']) ? $_POST['nm_torque'] : ''; ?>">
                                            </div>
                                        </div>

                                        <div class="step-actions mt-4">
                                            <button type="button" class="btn btn-primary btn-modern next-step">
                                                Devam Et <i class="fas fa-arrow-right ms-2"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Adım 2: Dosya Yükleme -->
                                    <div class="form-step" id="step-2">
                                        <div class="step-header mb-4">
                                            <h4 class="mb-2">
                                                <i class="fas fa-cloud-upload-alt me-2 text-primary"></i>Dosya Yükleme
                                            </h4>
                                            <p class="text-muted">ECU dosyanızı seçin ve isteğe bağlı notlarınızı ekleyin</p>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="file" class="form-label fw-semibold">ECU Dosyası *</label>
                                            <div class="file-upload-area modern" id="fileUploadArea">
                                                <input type="file" class="form-control" id="file" name="file" 
                                                       accept=".bin,.hex,.ecu,.ori,.mod,.zip,.rar" required style="display: none;">
                                                <div class="upload-content">
                                                    <div class="upload-icon">
                                                        <i class="fas fa-cloud-upload-alt"></i>
                                                    </div>
                                                    <h5 class="upload-title">Dosyanızı buraya sürükleyin</h5>
                                                    <p class="upload-subtitle">veya 
                                                        <button type="button" class="btn btn-link p-0 upload-button" onclick="document.getElementById('file').click()">
                                                            dosya seçmek için tıklayın
                                                        </button>
                                                    </p>
                                                    <div class="upload-info">
                                                        <div class="info-item">
                                                            <i class="fas fa-file-code me-1"></i>
                                                            <span>Desteklenen: <?php echo implode(', ', array_map(function($ext) { return '.' . $ext; }, ALLOWED_EXTENSIONS)); ?></span>
                                                        </div>
                                                        <div class="info-item">
                                                            <i class="fas fa-weight-hanging me-1"></i>
                                                            <span>Maks. boyut: <?php echo (MAX_FILE_SIZE / 1024 / 1024); ?>MB</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="invalid-feedback">Dosya seçimi zorunludur.</div>
                                        </div>
                                        
                                        <div class="mb-4" id="fileInfo" style="display: none;">
                                            <div class="file-preview">
                                                <div class="file-icon">
                                                    <i class="fas fa-file-alt"></i>
                                                </div>
                                                <div class="file-details">
                                                    <div class="file-name" id="fileName"></div>
                                                    <div class="file-size" id="fileSize"></div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-file" onclick="clearFile()">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="notes" class="form-label fw-semibold">Notlar ve Açıklamalar</label>
                                            <textarea class="form-control form-control-modern" id="notes" name="notes" rows="4" 
                                                      placeholder="Dosya hakkında özel notlarınız, istekleriniz veya dikkat edilmesi gereken konular..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                            <div class="form-help">
                                                <small class="text-muted">Bu bilgiler teknik ekibimize yardımcı olacaktır</small>
                                            </div>
                                        </div>

                                        <div class="step-actions mt-4">
                                            <button type="button" class="btn btn-outline-secondary btn-modern prev-step me-2">
                                                <i class="fas fa-arrow-left me-2"></i>Geri
                                            </button>
                                            <button type="button" class="btn btn-primary btn-modern next-step">
                                                Devam Et <i class="fas fa-arrow-right ms-2"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Adım 3: Özet ve Gönderim -->
                                    <div class="form-step" id="step-3">
                                        <div class="step-header mb-4">
                                            <h4 class="mb-2">
                                                <i class="fas fa-check-circle me-2 text-success"></i>Bilgileri Kontrol Edin
                                            </h4>
                                            <p class="text-muted">Yükleme işlemini tamamlamadan önce bilgilerinizi kontrol edin</p>
                                        </div>
                                        
                                        <div class="summary-card mb-4">
                                            <h6 class="summary-title">
                                                <i class="fas fa-car me-2"></i>Araç Bilgileri
                                            </h6>
                                            <div class="summary-content">
                                                <div class="summary-item">
                                                    <span class="label">Marka/Model:</span>
                                                    <span class="value" id="summary-brand-model">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="label">Model Yılı:</span>
                                                    <span class="value" id="summary-year">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="label">Plaka:</span>
                                                    <span class="value" id="summary-plate">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="label">ECU Tipi:</span>
                                                    <span class="value" id="summary-ecu">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="label">Motor Kodu:</span>
                                                    <span class="value" id="summary-engine">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="label">Güç/Tork:</span>
                                                    <span class="value" id="summary-power">-</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="summary-card mb-4">
                                            <h6 class="summary-title">
                                                <i class="fas fa-file me-2"></i>Dosya Bilgileri
                                            </h6>
                                            <div class="summary-content">
                                                <div class="summary-item">
                                                    <span class="label">Dosya Adı:</span>
                                                    <span class="value" id="summary-filename">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="label">Dosya Boyutu:</span>
                                                    <span class="value" id="summary-filesize">-</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="upload-terms mb-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="terms" required>
                                                <label class="form-check-label" for="terms">
                                                    <strong>Şartları kabul ediyorum:</strong> Yüklediğim dosyanın yasal olduğunu, telif hakkı ihlali içermediğini 
                                                    ve işletmeniz tarafından güvenli şekilde işlenebileceğini onaylıyorum.
                                                </label>
                                                <div class="invalid-feedback">Şartları kabul etmelisiniz.</div>
                                            </div>
                                        </div>

                                        <div class="step-actions">
                                            <button type="button" class="btn btn-outline-secondary btn-modern prev-step me-2">
                                                <i class="fas fa-arrow-left me-2"></i>Geri
                                            </button>
                                            <button type="submit" class="btn btn-success btn-modern btn-lg">
                                                <i class="fas fa-paper-plane me-2"></i>Dosyayı Yükle ve Gönder
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Bilgilendirme Kartı -->
                    <div class="info-card mb-4">
                        <div class="info-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Önemli Bilgiler
                            </h5>
                        </div>
                        <div class="info-body">
                            <div class="info-list">
                                <div class="info-item">
                                    <i class="fas fa-shield-alt text-success"></i>
                                    <div>
                                        <strong>Güvenli Şifreleme</strong>
                                        <span>Dosyalarınız SSL ile şifrelenir</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-clock text-info"></i>
                                    <div>
                                        <strong>Hızlı İşlem</strong>
                                        <span>24-48 saat içinde tamamlanır</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-bell text-warning"></i>
                                    <div>
                                        <strong>Otomatik Bildirim</strong>
                                        <span>Email ile bilgilendirilirsiniz</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-user-lock text-primary"></i>
                                    <div>
                                        <strong>Gizlilik</strong>
                                        <span>Sadece siz erişebilirsiniz</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-code-branch text-secondary"></i>
                                    <div>
                                        <strong>GUID Sistem</strong>
                                        <span>Gelişmiş güvenlik protokolü</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Desteklenen Formatlar -->
                    <div class="info-card">
                        <div class="info-header">
                            <h5 class="mb-0">
                                <i class="fas fa-file-code me-2"></i>Desteklenen Formatlar
                            </h5>
                        </div>
                        <div class="info-body">
                            <div class="format-grid">
                                <?php foreach (ALLOWED_EXTENSIONS as $ext): ?>
                                    <div class="format-item">
                                        <i class="fas fa-file-alt"></i>
                                        <span>.<?php echo $ext; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="format-note">
                                <small class="text-muted">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Diğer formatlar için bizimle iletişime geçin
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
/* Modern Upload Wizard Stilleri */
.upload-wizard {
    max-width: 100%;
}

.wizard-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 2rem;
    padding: 0 1rem;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.step.active .step-number {
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.step.completed .step-number {
    background: #28a745;
    color: white;
}

.step-title {
    font-size: 0.85rem;
    font-weight: 500;
    color: #6c757d;
    text-align: center;
}

.step.active .step-title {
    color: #495057;
    font-weight: 600;
}

.step-line {
    height: 2px;
    width: 80px;
    background: #e9ecef;
    margin: 0 1rem;
    margin-top: 20px;
}

.step.active + .step-line {
    background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
}

/* Form Steps */
.form-step {
    display: none;
}

.form-step.active {
    display: block;
    animation: fadeInUp 0.5s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.step-header h4 {
    color: #495057;
    font-weight: 600;
}

.step-header p {
    font-size: 0.95rem;
}

/* Modern Form Controls */
.form-control-modern {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
    background: #fff;
}

.form-control-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-label {
    color: #495057;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.form-help {
    margin-top: 0.25rem;
}

/* Modern File Upload */
.file-upload-area.modern {
    border: 2px dashed #e9ecef;
    border-radius: 12px;
    padding: 3rem 2rem;
    text-align: center;
    transition: all 0.3s ease;
    background: #f8f9fa;
    cursor: pointer;
}

.file-upload-area.modern:hover,
.file-upload-area.modern.dragover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.upload-icon {
    font-size: 3rem;
    color: #667eea;
    margin-bottom: 1rem;
}

.upload-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.upload-subtitle {
    color: #6c757d;
    margin-bottom: 1.5rem;
}

.upload-button {
    color: #667eea !important;
    font-weight: 600;
    text-decoration: none !important;
}

.upload-button:hover {
    color: #5a67d8 !important;
}

.upload-info {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.info-item {
    color: #6c757d;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
}

.info-item i {
    color: #667eea;
}

/* File Preview */
.file-preview {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
}

.file-icon {
    width: 48px;
    height: 48px;
    background: #667eea;
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-right: 1rem;
}

.file-details {
    flex: 1;
}

.file-name {
    font-weight: 600;
    color: #495057;
}

.file-size {
    color: #6c757d;
    font-size: 0.9rem;
}

.remove-file {
    margin-left: auto;
}

/* Summary Cards */
.summary-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
}

.summary-title {
    color: #495057;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.summary-item:last-child {
    margin-bottom: 0;
}

.summary-item .label {
    color: #6c757d;
    font-weight: 500;
}

.summary-item .value {
    color: #495057;
    font-weight: 600;
}

/* Upload Terms */
.upload-terms {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 1rem;
}

.upload-terms .form-check-label {
    color: #856404;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Buttons */
.btn-modern {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-modern:hover {
    transform: translateY(-1px);
}

.step-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef;
}

/* Info Cards */
.info-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.info-header {
    background: #f8f9fa;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e9ecef;
}

.info-header h5 {
    margin: 0;
    color: #495057;
    font-weight: 600;
}

.info-body {
    padding: 1.25rem;
}

.info-list {
    space-y: 1rem;
}

.info-list .info-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.info-list .info-item:last-child {
    margin-bottom: 0;
}

.info-list .info-item i {
    font-size: 1.1rem;
    margin-right: 0.75rem;
    margin-top: 0.125rem;
    flex-shrink: 0;
}

.info-list .info-item div {
    flex: 1;
}

.info-list .info-item strong {
    display: block;
    color: #495057;
    font-weight: 600;
    margin-bottom: 0.125rem;
}

.info-list .info-item span {
    color: #6c757d;
    font-size: 0.9rem;
}

/* Format Grid */
.format-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.format-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.format-item i {
    font-size: 1.25rem;
    color: #667eea;
    margin-bottom: 0.25rem;
}

.format-item span {
    font-size: 0.8rem;
    font-weight: 500;
    color: #495057;
}

.format-note {
    text-align: center;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

/* Alert Modern */
.alert-modern {
    border: none;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

/* Responsive */
@media (max-width: 767.98px) {
    .wizard-steps {
        flex-direction: column;
        gap: 1rem;
    }
    
    .step-line {
        width: 2px;
        height: 40px;
        margin: 0.5rem 0;
    }
    
    .file-upload-area.modern {
        padding: 2rem 1rem;
    }
    
    .upload-info {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .step-actions {
        flex-direction: column;
        gap: 1rem;
    }
    
    .format-grid {
        grid-template-columns: repeat(auto-fit, minmax(60px, 1fr));
    }
}
</style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Wizard Control
let currentStep = 1;
const totalSteps = 3;

// GUID validation function
function isValidGUID(guid) {
    const guidPattern = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
    return guidPattern.test(guid);
}

// Step Navigation
function showStep(step) {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active', 'completed'));
    
    // Show current step
    document.getElementById(`step-${step}`).classList.add('active');
    
    // Update step indicators
    for (let i = 1; i <= totalSteps; i++) {
        const stepEl = document.querySelector(`.step[data-step="${i}"]`);
        if (i < step) {
            stepEl.classList.add('completed');
        } else if (i === step) {
            stepEl.classList.add('active');
        }
    }
    
    currentStep = step;
}

// Next Step Button
document.querySelectorAll('.next-step').forEach(btn => {
    btn.addEventListener('click', function() {
        if (validateCurrentStep()) {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
                if (currentStep === 3) {
                    updateSummary();
                }
            }
        }
    });
});

// Previous Step Button
document.querySelectorAll('.prev-step').forEach(btn => {
    btn.addEventListener('click', function() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });
});

// Validate Current Step
function validateCurrentStep() {
    if (currentStep === 1) {
        const brand = document.getElementById('brand_id').value;
        const model = document.getElementById('model_id').value;
        const year = document.getElementById('year').value;
        
        if (!brand) {
            showError('Marka seçimi zorunludur.');
            return false;
        }
        if (!isValidGUID(brand)) {
            showError('Geçersiz marka GUID formatı.');
            return false;
        }
        if (!model) {
            showError('Model seçimi zorunludur.');
            return false;
        }
        if (!isValidGUID(model)) {
            showError('Geçersiz model GUID formatı.');
            return false;
        }
        if (!year || year < 1990 || year > new Date().getFullYear() + 1) {
            showError('Geçerli bir model yılı girin.');
            return false;
        }
    } else if (currentStep === 2) {
        const file = document.getElementById('file').files[0];
        if (!file) {
            showError('Dosya seçimi zorunludur.');
            return false;
        }
    }
    return true;
}

// Show Error
function showError(message) {
    // Geçici error mesajı göster
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('main').insertBefore(alert, document.querySelector('.upload-wizard'));
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Update Summary
function updateSummary() {
    const brand = document.getElementById('brand_id').selectedOptions[0]?.text || '-';
    const model = document.getElementById('model_id').selectedOptions[0]?.text || '-';
    const year = document.getElementById('year').value || '-';
    const plate = document.getElementById('plate').value ? document.getElementById('plate').value.toUpperCase() : 'Belirtilmedi';
    const ecu = document.getElementById('ecu_type').value || 'Belirtilmedi';
    const engine = document.getElementById('engine_code').value || 'Belirtilmedi';
    const hp = document.getElementById('hp_power').value;
    const nm = document.getElementById('nm_torque').value;
    
    let power = 'Belirtilmedi';
    if (hp && nm) {
        power = `${hp} HP / ${nm} Nm`;
    } else if (hp) {
        power = `${hp} HP`;
    } else if (nm) {
        power = `${nm} Nm`;
    }
    
    document.getElementById('summary-brand-model').textContent = `${brand} ${model}`;
    document.getElementById('summary-year').textContent = year;
    document.getElementById('summary-plate').textContent = plate;
    document.getElementById('summary-ecu').textContent = ecu;
    document.getElementById('summary-engine').textContent = engine;
    document.getElementById('summary-power').textContent = power;
    
    const file = document.getElementById('file').files[0];
    if (file) {
        document.getElementById('summary-filename').textContent = file.name;
        document.getElementById('summary-filesize').textContent = formatFileSize(file.size);
    }
}

// Marka değiştiğinde modelleri yükle
document.getElementById('brand_id').addEventListener('change', function() {
    const brandId = this.value;
    const modelSelect = document.getElementById('model_id');
    
    console.log('Brand changed:', brandId);
    
    if (brandId && isValidGUID(brandId)) {
        console.log('Valid GUID, fetching models...');
        modelSelect.innerHTML = '<option value="">Yüklenyor...</option>';
        modelSelect.disabled = true;
        
        fetch(`?get_models=1&brand_id=${encodeURIComponent(brandId)}`)
            .then(response => {
                console.log('Fetch response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                
                if (data.error) {
                    console.error('Server error:', data.error);
                    modelSelect.innerHTML = `<option value="">Hata: ${data.error}</option>`;
                    return;
                }
                
                if (!Array.isArray(data)) {
                    console.error('Invalid data format:', data);
                    modelSelect.innerHTML = '<option value="">Geçersiz veri formatı</option>';
                    return;
                }
                
                if (data.length === 0) {
                    console.warn('No models found for brand:', brandId);
                    modelSelect.innerHTML = '<option value="">Bu marka için model bulunamadı</option>';
                    return;
                }
                
                modelSelect.innerHTML = '<option value="">Model Seçin</option>';
                data.forEach(model => {
                    const displayName = model.display_name || model.name;
                    modelSelect.innerHTML += `<option value="${model.id}" data-guid="${model.id}">${displayName}</option>`;
                });
                modelSelect.disabled = false;
                console.log('Models loaded successfully, count:', data.length);
            })
            .catch(error => {
                console.error('Fetch error:', error);
                modelSelect.innerHTML = `<option value="">Bağlantı hatası</option>`;
                modelSelect.disabled = true;
            });
    } else if (brandId && !isValidGUID(brandId)) {
        console.error('Invalid GUID format for brand:', brandId);
        modelSelect.innerHTML = '<option value="">Geçersiz marka ID</option>';
        modelSelect.disabled = true;
    } else {
        console.log('No brand selected or empty value');
        modelSelect.innerHTML = '<option value="">Önce marka seçin</option>';
        modelSelect.disabled = true;
    }
});

// Dosya yükleme işlemleri
const fileInput = document.getElementById('file');
const fileUploadArea = document.getElementById('fileUploadArea');
const fileInfo = document.getElementById('fileInfo');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');

// Drag & Drop
fileUploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('dragover');
});

fileUploadArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
});

fileUploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        showFileInfo(files[0]);
    }
});

fileUploadArea.addEventListener('click', function() {
    fileInput.click();
});

fileInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        showFileInfo(this.files[0]);
    }
});

function showFileInfo(file) {
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    fileInfo.style.display = 'block';
}

function clearFile() {
    fileInput.value = '';
    fileInfo.style.display = 'none';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        const forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                // GUID validations
                const brandId = document.getElementById('brand_id').value;
                const modelId = document.getElementById('model_id').value;
                
                if (brandId && !isValidGUID(brandId)) {
                    event.preventDefault();
                    event.stopPropagation();
                    showError('Geçersiz marka GUID formatı: ' + brandId);
                    return false;
                }
                
                if (modelId && !isValidGUID(modelId)) {
                    event.preventDefault();
                    event.stopPropagation();
                    showError('Geçersiz model GUID formatı: ' + modelId);
                    return false;
                }
                
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Initialize
showStep(1);
</script>

<?php
// Footer include
include '../includes/user_footer.php';
?>