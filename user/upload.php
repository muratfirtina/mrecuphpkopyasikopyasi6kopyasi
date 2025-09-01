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
if (isset($_GET['get_series']) && isset($_GET['model_id'])) {
    try {
        require_once '../config/config.php';
        require_once '../config/database.php';
        
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json');
        
        if (!isLoggedIn()) {
            echo json_encode(['error' => 'Giriş gerekli']);
            exit;
        }
        
        $fileManager = new FileManager($pdo);
        $modelId = sanitize($_GET['model_id']);
        
        if (!isValidUUID($modelId)) {
            echo json_encode(['error' => 'Geçersiz model ID formatı']);
            exit;
        }
        
        $series = $fileManager->getSeriesByModel($modelId);
        echo json_encode($series);
        
    } catch (Exception $e) {
        error_log('AJAX Series Error: ' . $e->getMessage());
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode(['error' => 'Server hatası oluştu']);
    }
    exit;
}
if (isset($_GET['get_engines']) && isset($_GET['series_id'])) {
    try {
        require_once '../config/config.php';
        require_once '../config/database.php';
        
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json');
        
        if (!isLoggedIn()) {
            echo json_encode(['error' => 'Giriş gerekli']);
            exit;
        }
        
        $fileManager = new FileManager($pdo);
        $seriesId = sanitize($_GET['series_id']);
        
        if (!isValidUUID($seriesId)) {
            echo json_encode(['error' => 'Geçersiz seri ID formatı']);
            exit;
        }
        
        $engines = $fileManager->getEnginesBySeries($seriesId);
        echo json_encode($engines);
        
    } catch (Exception $e) {
        error_log('AJAX Engine Error: ' . $e->getMessage());
        if (ob_get_level()) {
            ob_clean();
        }
        echo json_encode(['error' => 'Server hatası oluştu']);
    }
    exit;
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
    
    // ECU ve Device modellerini dahil et
    require_once '../includes/EcuModel.php';
    require_once '../includes/DeviceModel.php';
    $ecuModel = new EcuModel($pdo);
    $deviceModel = new DeviceModel($pdo);
} catch (Exception $e) {
    error_log('Class initialization error: ' . $e->getMessage());
    echo "<div class='alert alert-danger'>Sınıf başlatma hatası: " . $e->getMessage() . "</div>";
    exit;
}

// TERS KREDİ SİSTEMİ: Kredi durumunu güncelle
try {
    $userCreditDetails = $user->getUserCreditDetails($_SESSION['user_id']);
    $_SESSION['credits'] = $userCreditDetails['available_credits'];
    $_SESSION['credit_quota'] = $userCreditDetails['credit_quota'];
    $_SESSION['credit_used'] = $userCreditDetails['credit_used'];
    
    echo '<!-- DEBUG: TERS KREDİ SİSTEMİ - Kota: ' . $userCreditDetails['credit_quota'] . ', Kullanılan: ' . $userCreditDetails['credit_used'] . ', Kullanılabilir: ' . $userCreditDetails['available_credits'] . ' -->';
} catch (Exception $e) {
    echo '<!-- DEBUG: Credits güncelleme hatası: ' . $e->getMessage() . ' -->';
    $_SESSION['credits'] = 0;
    $_SESSION['credit_quota'] = 0;
    $_SESSION['credit_used'] = 0;
}

$error = '';
$success = '';

// Session mesajlarını al ve temizle - PRG pattern için
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// TERS KREDİ SİSTEMİ: Dosya yükleme kredi kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    error_log('User file upload attempt started');
    
    try {
        // Kullanıcının kredi durumunu kontrol et
        $creditCheck = $user->canUserUploadFile($_SESSION['user_id']);
        
        if (!$creditCheck['can_upload']) {
            $_SESSION['error'] = $creditCheck['message'];
            error_log('Upload rejected due to credit limit: ' . $creditCheck['message']);
            header('Location: upload.php');
            exit;
        } else {
            // Araç verilerini hazırla
            $vehicleData = [
                'brand_id' => sanitize($_POST['brand_id']),
                'model_id' => sanitize($_POST['model_id']),
                'series_id' => !empty($_POST['series_id']) ? sanitize($_POST['series_id']) : null,
                'engine_id' => !empty($_POST['engine_id']) ? sanitize($_POST['engine_id']) : null,
                'year' => date('Y'),
                'plate' => !empty($_POST['plate']) ? strtoupper(sanitize($_POST['plate'])) : null,
                'ecu_id' => !empty($_POST['ecu_id']) && isValidUUID($_POST['ecu_id']) ? sanitize($_POST['ecu_id']) : null,

                'device_id' => !empty($_POST['device_id']) && isValidUUID($_POST['device_id']) ? sanitize($_POST['device_id']) : null,

                'gearbox_type' => !empty($_POST['gearbox_type']) ? sanitize($_POST['gearbox_type']) : 'Manual',
                'fuel_type' => !empty($_POST['fuel_type']) ? sanitize($_POST['fuel_type']) : 'Benzin',
                'hp_power' => null,
                'nm_torque' => null,
                'kilometer' => !empty($_POST['kilometer']) ? intval($_POST['kilometer']) : null
            ];

            // Notlar alanını kontrol et - zorunlu alan
            if (empty($_POST['notes']) || trim($_POST['notes']) === '') {
                $_SESSION['error'] = 'Notlar ve açıklamalar alanı zorunludur!';
                error_log('Upload rejected: Notes field is required');
                header('Location: upload.php');
                exit;
            }
            
            $notes = sanitize($_POST['notes']);
            
            // Dosya yükleme işlemine devam et
            $uploadResult = $fileManager->uploadFile($_SESSION['user_id'], $_FILES['file'], $vehicleData, $notes);
            
            if ($uploadResult['success']) {
                error_log('File upload successful: ' . $uploadResult['upload_id'] ?? 'unknown');
                
                // Session kredi bilgisini güncelle
                $userCreditDetails = $user->getUserCreditDetails($_SESSION['user_id']);
                $_SESSION['credits'] = $userCreditDetails['available_credits'];
                $_SESSION['credit_quota'] = $userCreditDetails['credit_quota'];
                $_SESSION['credit_used'] = $userCreditDetails['credit_used'];
                
                // Başarılı yükleme sonrası redirect - PRG pattern
                $_SESSION['success'] = $uploadResult['message'];
                header('Location: upload.php');
                exit;
            } else {
                $_SESSION['error'] = $uploadResult['message'];
                error_log('File upload failed: ' . $uploadResult['message']);
                header('Location: upload.php');
                exit;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Dosya yükleme sırasında hata oluştu: ' . $e->getMessage();
        error_log('Upload processing error: ' . $e->getMessage());
        header('Location: upload.php');
        exit;
    }
}

// Araç markalarını getir
try {
    $brands = $fileManager->getBrands();
    echo '<!-- DEBUG: Markalar getirildi, adet: ' . count($brands) . ' -->';
} catch (Exception $e) {
    echo '<!-- DEBUG: Marka getirme hatası: ' . $e->getMessage() . ' -->';
    $brands = [];
}

// ECU'ları getir
try {
    $ecus = $ecuModel->getAllEcus('name', 'ASC');
    echo '<!-- DEBUG: ECU\'lar getirildi, adet: ' . count($ecus) . ' -->';
} catch (Exception $e) {
    echo '<!-- DEBUG: ECU getirme hatası: ' . $e->getMessage() . ' -->';
    $ecus = [];
}

// Device'ları getir
try {
    $devices = $deviceModel->getAllDevices('name', 'ASC');
    echo '<!-- DEBUG: Device\'lar getirildi, adet: ' . count($devices) . ' -->';
} catch (Exception $e) {
    echo '<!-- DEBUG: Device getirme hatası: ' . $e->getMessage() . ' -->';
    $devices = [];
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
        <?php include '../includes/user_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 mb-0">
                        <i class="bi bi-upload me-2 text-primary"></i>Dosya Yükle
                    </h1>
                    <p class="text-muted mb-0">ECU dosyanızı güvenli bir şekilde yükleyin ve işletmemize gönderin</p>
                </div>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="files.php" class="btn btn-outline-secondary">
                            <i class="bi bi-folder me-1"></i>Dosyalarım
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-modern" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle me-3 fa-lg"></i>
                        <div>
                            <strong>Hata!</strong> <?php echo $error; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-modern" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle me-3 fa-lg"></i>
                        <div>
                            <strong>Başarılı!</strong> <?php echo $success; ?>
                            <div class="mt-2">
                                <a href="files.php" class="btn btn-success btn-sm">
                                    <i class="bi bi-folder me-1"></i>Dosyalarımı Görüntüle
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
                                                <i class="bi bi-car me-2 text-primary"></i>Araç Bilgileri
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
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <label for="model_id" class="form-label fw-semibold">Model *</label>
                                                <select class="form-select form-control-modern" id="model_id" name="model_id" required disabled>
                                                    <option value="">Önce marka seçin</option>
                                                </select>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="series_id" class="form-label fw-semibold">Seri *</label>
                                                <select class="form-select form-control-modern" id="series_id" name="series_id" required disabled>
                                                    <option value="">Önce model seçin</option>
                                                </select>
                                            </div>

                                            <div class="col-md-3">
                                                <label for="engine_id" class="form-label fw-semibold">Motor *</label>
                                                <select class="form-select form-control-modern" id="engine_id" name="engine_id" required disabled>
                                                    <option value="">Önce seri seçin</option>
                                                </select>
                                            </div>
                                            
                                            <!-- <div class="col-md-3">
                                                <label for="year" class="form-label fw-semibold">Model Yılı (Opsiyonel)</label>
                                                <input type="number" class="form-control form-control-modern" id="year" name="year" 
                                                       min="1990" max="<?php echo date('Y') + 1; ?>" 
                                                       value="<?php echo isset($_POST['year']) ? $_POST['year'] : ''; ?>" 
                                                       placeholder="<?php echo date('Y'); ?>">
                                                <div class="invalid-feedback">Geçerli bir model yılı girin.</div>
                                            </div> -->
                                            
                                            <div class="col-md-3">
                                                <label for="plate" class="form-label fw-semibold">Plaka *</label>
                                                <input type="text" class="form-control form-control-modern" id="plate" name="plate" 
                                                       placeholder="34 ABC 123" 
                                                       value="<?php echo isset($_POST['plate']) ? htmlspecialchars($_POST['plate']) : ''; ?>" 
                                                       style="text-transform: uppercase;" required>
                                                <div class="form-help">
                                                    <small class="text-muted">Araç plaka numarası</small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <label for="kilometer" class="form-label fw-semibold">Kilometre</label>
                                                <input type="number" class="form-control form-control-modern" id="kilometer" name="kilometer" 
                                                       placeholder="150000" min="0" max="999999"
                                                       value="<?php echo isset($_POST['kilometer']) ? htmlspecialchars($_POST['kilometer']) : ''; ?>">
                                                <div class="form-help">
                                                    <small class="text-muted">Aracın mevcut kilometre değeri (km)</small>
                                                </div>
                                            </div>
                                        
                                        
                                            <div class="col-md-3">
                                                <label for="ecu_id" class="form-label fw-semibold">ECU Tipi *</label>
                                                <select class="form-select form-control-modern" id="ecu_id" name="ecu_id" required>
                                                    <option value="">ECU Seçin</option>
                                                    <?php foreach ($ecus as $ecu): ?>
                                                        <option value="<?php echo htmlspecialchars($ecu['id']); ?>"
                                                                <?php echo (isset($_POST['ecu_id']) && $_POST['ecu_id'] == $ecu['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($ecu['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-help">
                                                    <small class="text-muted">
                                                        <i class="bi bi-microchip me-1"></i>ECU tipi seçin
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <label for="device_id" class="form-label fw-semibold">Kullanılan Cihaz *</label>
                                                <select class="form-select form-control-modern" id="device_id" name="device_id" required>
                                                    <option value="">Cihaz Seçin</option>
                                                    <?php foreach ($devices as $device): ?>
                                                        <option value="<?php echo htmlspecialchars($device['id']); ?>"
                                                                <?php echo (isset($_POST['device_id']) && $_POST['device_id'] == $device['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($device['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-help">
                                                    <small class="text-muted">
                                                        <i class="bi bi-tools me-1"></i>Tuning cihazı seçin
                                                    </small>
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
                                            
                                            <!-- <div class="col-md-3">
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
                                            </div> -->
                                        </div>

                                        <div class="step-actions mt-4">
                                            <button type="button" class="btn btn-primary btn-modern next-step">
                                                Devam Et <i class="bi bi-arrow-right ms-2"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Adım 2: Dosya Yükleme -->
                                    <div class="form-step" id="step-2">
                                        <div class="step-header mb-4">
                                            <h4 class="mb-2">
                                                <i class="bi bi-cloud-upload-alt me-2 text-primary"></i>Dosya Yükleme
                                            </h4>
                                            <p class="text-muted">ECU dosyanızı seçin ve isteğe bağlı notlarınızı ekleyin</p>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="file" class="form-label fw-semibold">ECU Dosyası *</label>
                                            <div class="file-upload-area modern" id="fileUploadArea">
                                                <input type="file" class="form-control" id="file" name="file" 
                                                       required style="display: none;">
                                                <div class="upload-content">
                                                    <div class="upload-icon">
                                                        <i class="bi bi-cloud-upload-alt"></i>
                                                    </div>
                                                    <h5 class="upload-title">Dosyanızı buraya sürükleyin</h5>
                                                    <p class="upload-subtitle">veya 
                                                        <button type="button" class="btn btn-link p-0 upload-button" onclick="document.getElementById('file').click()">
                                                            dosya seçmek için tıklayın
                                                        </button>
                                                    </p>
                                                    <div class="upload-info">
                                                        <div class="info-item">
                                                            <i class="bi bi-file-code me-1"></i>
                                                            <span>Desteklenen: Tüm dosya türleri</span>
                                                        </div>
                                                        <div class="info-item">
                                                            <i class="bi bi-weight-hanging me-1"></i>
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
                                                    <i class="bi bi-file-alt"></i>
                                                </div>
                                                <div class="file-details">
                                                    <div class="file-name" id="fileName"></div>
                                                    <div class="file-size" id="fileSize"></div>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-file" onclick="clearFile()">
                                                    <i class="bi bi-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="notes" class="form-label fw-semibold">Notlar ve Açıklamalar *</label>
                                            <textarea class="form-control form-control-modern" id="notes" name="notes" rows="4" required
                                                      placeholder="Dosya hakkında özel notlarınız, istekleriniz veya dikkat edilmesi gereken konular..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                            <div class="form-help">
                                                <small class="text-muted">Bu bilgiler teknik ekibimize yardımcı olacaktır</small>
                                            </div>
                                        </div>

                                        <div class="step-actions mt-4">
                                            <button type="button" class="btn btn-outline-secondary btn-modern prev-step me-2">
                                                <i class="bi bi-arrow-left me-2"></i>Geri
                                            </button>
                                            <button type="button" class="btn btn-primary btn-modern next-step">
                                                Devam Et <i class="bi bi-arrow-right ms-2"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Adım 3: Özet ve Gönderim -->
                                    <div class="form-step" id="step-3">
                                        <div class="step-header mb-4">
                                            <h4 class="mb-2">
                                                <i class="bi bi-check-circle me-2 text-success"></i>Bilgileri Kontrol Edin
                                            </h4>
                                            <p class="text-muted">Yükleme işlemini tamamlamadan önce bilgilerinizi kontrol edin</p>
                                        </div>
                                        
                                        <div class="summary-card mb-4">
                                            <h6 class="summary-title">
                                                <i class="bi bi-car me-2"></i>Araç Bilgileri
                                            </h6>
                                            <div class="summary-content">
                                                <div class="summary-item">
                                                    <span class="label">Marka/Model:</span>
                                                    <span class="value" id="summary-brand-model">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="label">Seri:</span>
                                                    <span class="value" id="summary-series-year">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="label">Motor:</span>
                                                    <span class="value" id="summary-engine">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="label">Plaka:</span>
                                                    <span class="value" id="summary-plate">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="label">Kilometre:</span>
                                                    <span class="value" id="summary-kilometer">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="label">ECU Tipi:</span>
                                                    <span class="value" id="summary-ecu">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="label">Kullanılan Cihaz:</span>
                                                    <span class="value" id="summary-device">-</span>
                                                </div>
                                                <div class="summary-item">
                                                    <span class="label">Şanzuman/Yakıt:</span>
                                                    <span class="value" id="summary-power">-</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="summary-card mb-4">
                                            <h6 class="summary-title">
                                                <i class="bi bi-file me-2"></i>Dosya Bilgileri
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
                                                <i class="bi bi-arrow-left me-2"></i>Geri
                                            </button>
                                            <button type="submit" class="btn btn-success btn-modern btn-lg">
                                                <i class="bi bi-paper-plane me-2"></i>Dosyayı Yükle ve Gönder
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Özet Paneli -->
                    <div class="info-card mb-4">
                        <div class="info-header">
                            <h5 class="mb-0">
                                <i class="bi bi-info-circle me-2"></i>Bilgi Özeti
                            </h5>
                            <div class="info-body">
                                <div class="info-list">
                                    <div class="info-item">
                                        <div id="summary">
                                            <!-- JavaScript ile doldurulacak -->
                                        </div>
                                    </div>
                                </div>
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

.file-upload-area.modern.file-selected {
    border-color: #28a745;
    background: rgba(40, 167, 69, 0.05);
}

.file-upload-area.modern.file-selected .upload-icon {
    color: #28a745;
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
                    updateSummary(); // Adım 3'e geçerken özeti güncelle
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

// Validate Current Step - GÜNCELLENMIŞ!
function validateCurrentStep() {
    if (currentStep === 1) {
        const brand = document.getElementById('brand_id').value;
        const model = document.getElementById('model_id').value;
        const series = document.getElementById('series_id').value;
        const engine = document.getElementById('engine_id').value;
        const plate = document.getElementById('plate').value.trim();
        const ecu = document.getElementById('ecu_id').value;
        const device = document.getElementById('device_id').value;
        
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
        if (!series) {
            showError('Seri seçimi zorunludur.');
            return false;
        }
        if (!isValidGUID(series)) {
            showError('Geçersiz seri GUID formatı.');
            return false;
        }
        if (!engine) {
            showError('Motor seçimi zorunludur.');
            return false;
        }
        if (!isValidGUID(engine)) {
            showError('Geçersiz motor GUID formatı.');
            return false;
        }
        if (!plate) {
            showError('Plaka girişi zorunludur.');
            return false;
        }
        if (!ecu) {
            showError('ECU tipi seçimi zorunludur.');
            return false;
        }
        if (!isValidGUID(ecu)) {
            showError('Geçersiz ECU GUID formatı.');
            return false;
        }
        if (!device) {
            showError('Cihaz seçimi zorunludur.');
            return false;
        }
        if (!isValidGUID(device)) {
            showError('Geçersiz cihaz GUID formatı.');
            return false;
        }
    } else if (currentStep === 2) {
        const file = document.getElementById('file').files[0];
        const notes = document.getElementById('notes').value.trim();
        
        if (!file) {
            showError('Dosya seçimi zorunludur.');
            return false;
        }
        if (!notes) {
            showError('Notlar ve açıklamalar girilmesi zorunludur.');
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
        <i class="bi bi-exclamation-triangle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('main').insertBefore(alert, document.querySelector('.upload-wizard'));
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Validate Current Step - TAM YENİLENMİŞ VE GELİŞTİRİLMİŞ!
function validateCurrentStep() {
    // Önce tüm hata mesajlarını temizle
    clearAllValidationErrors();
    
    if (currentStep === 1) {
        const fields = [
            { id: 'brand_id', name: 'Marka', isGuid: true },
            { id: 'model_id', name: 'Model', isGuid: true },
            { id: 'series_id', name: 'Seri', isGuid: true },
            { id: 'engine_id', name: 'Motor', isGuid: true },
            { id: 'plate', name: 'Plaka', isGuid: false },
            { id: 'ecu_id', name: 'ECU Tipi', isGuid: true },
            { id: 'device_id', name: 'Kullanılan Cihaz', isGuid: true }
        ];
        
        let hasError = false;
        
        for (let field of fields) {
            const element = document.getElementById(field.id);
            const value = element.value.trim();
            
            // Boş kontrolü
            if (!value) {
                showFieldError(element, `${field.name} seçimi zorunludur.`);
                if (!hasError) {
                    element.focus();
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    hasError = true;
                }
            } else {
                // GUID kontrolü (sadece GUID alanı için)
                if (field.isGuid && !isValidGUID(value)) {
                    showFieldError(element, `Geçersiz ${field.name} formatı.`);
                    if (!hasError) {
                        element.focus();
                        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        hasError = true;
                    }
                } else {
                    // Alan başarılı ise hata durumunu temizle
                    clearFieldError(element);
                }
            }
        }
        
        if (hasError) {
            showError('Lütfen tüm zorunlu alanları doldurun.');
        }
        
        return !hasError;
        
    } else if (currentStep === 2) {
        let hasError = false;
        
        // Dosya kontrolü
        const fileInput = document.getElementById('file');
        if (!fileInput.files || fileInput.files.length === 0) {
            showFieldError(fileInput, 'Dosya seçimi zorunludur.');
            hasError = true;
        } else {
            clearFieldError(fileInput);
        }
        
        // Notlar kontrolü
        const notesInput = document.getElementById('notes');
        const notes = notesInput.value.trim();
        if (!notes) {
            showFieldError(notesInput, 'Notlar ve açıklamalar girilmesi zorunludur.');
            if (!hasError) {
                notesInput.focus();
                notesInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            hasError = true;
        } else {
            clearFieldError(notesInput);
        }
        
        if (hasError) {
            showError('Lütfen tüm zorunlu alanları doldurun.');
        }
        
        return !hasError;
        
    } else if (currentStep === 3) {
        const termsCheckbox = document.getElementById('terms');
        if (!termsCheckbox.checked) {
            showFieldError(termsCheckbox, 'Şartları kabul etmelisiniz.');
            termsCheckbox.focus();
            termsCheckbox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            showError('Şartları kabul etmelisiniz.');
            return false;
        } else {
            clearFieldError(termsCheckbox);
        }
    }
    
    return true;
}

// Özeti güncelle - TAMAMEN YENİ VE GÜNCELLENMIŞ!
function updateSummary() {
    // Değerleri al
    const brand = document.getElementById('brand_id').options[document.getElementById('brand_id').selectedIndex]?.text || 'Seçilmedi';
    const model = document.getElementById('model_id').options[document.getElementById('model_id').selectedIndex]?.text || 'Seçilmedi';
    const series = document.getElementById('series_id').options[document.getElementById('series_id').selectedIndex]?.text || 'Seçilmedi';
    const engine = document.getElementById('engine_id').options[document.getElementById('engine_id').selectedIndex]?.text || 'Seçilmedi';
    const plate = document.getElementById('plate').value.toUpperCase() || 'Belirtilmedi';
    const kilometer = document.getElementById('kilometer').value || 'Belirtilmedi';
    
    // ECU bilgisi - sadece dropdown'dan al
    const ecuDropdown = document.getElementById('ecu_id').selectedOptions[0]?.text || '';
    const ecu = (ecuDropdown && ecuDropdown !== 'ECU Seçin') ? ecuDropdown : 'Belirtilmedi';
    
    // Cihaz bilgisi
    const device = document.getElementById('device_id').selectedOptions[0]?.text || 'Belirtilmedi';
    const deviceFormatted = (device === 'Cihaz Seçin') ? 'Belirtilmedi' : device;
    
    const gearbox = document.getElementById('gearbox_type').value || 'Manuel';
    const fuel = document.getElementById('fuel_type').value || 'Benzin';

    // Sidebar özeti güncelle
    const summaryElement = document.getElementById('summary');
    if (summaryElement) {
        summaryElement.innerHTML = `
            <div class="summary-item"><strong>Marka:</strong> ${brand}</div>
            <div class="summary-item"><strong>Model:</strong> ${model}</div>
            <div class="summary-item"><strong>Seri:</strong> ${series}</div>
            <div class="summary-item"><strong>Motor:</strong> ${engine}</div>
            <div class="summary-item"><strong>Plaka:</strong> ${plate}</div>
            <div class="summary-item"><strong>Kilometre:</strong> ${kilometer} km</div>
            <div class="summary-item"><strong>ECU:</strong> ${ecu}</div>
            <div class="summary-item"><strong>Cihaz:</strong> ${deviceFormatted}</div>
        `;
    }

    // Step 3 detaylı özetini güncelle
    const summaryBrandModel = document.getElementById('summary-brand-model');
    if (summaryBrandModel) {
        summaryBrandModel.textContent = `${brand} ${model}`;
    }
    
    const summaryYear = document.getElementById('summary-series-year');
    if (summaryYear) {
        summaryYear.textContent = series;
    }
    
    const summaryPlate = document.getElementById('summary-plate');
    if (summaryPlate) {
        summaryPlate.textContent = plate;
    }
    
    const summaryKilometer = document.getElementById('summary-kilometer');
    if (summaryKilometer) {
        summaryKilometer.textContent = kilometer + ' km';
    }
    
    const summaryEcu = document.getElementById('summary-ecu');
    if (summaryEcu) {
        summaryEcu.textContent = ecu;
    }
    
    const summaryDevice = document.getElementById('summary-device');
    if (summaryDevice) {
        summaryDevice.textContent = deviceFormatted;
    }
    
    const summaryEngine = document.getElementById('summary-engine');
    if (summaryEngine) {
        summaryEngine.textContent = engine;
    }
    
    const summaryPower = document.getElementById('summary-power');
    if (summaryPower) {
        summaryPower.textContent = `${gearbox} / ${fuel}`;
    }

    // Dosya bilgisi varsa güncelle
    const file = document.getElementById('file').files[0];
    const summaryFilename = document.getElementById('summary-filename');
    const summaryFilesize = document.getElementById('summary-filesize');
    
    if (file && summaryFilename && summaryFilesize) {
        summaryFilename.textContent = file.name;
        summaryFilesize.textContent = formatFileSize(file.size);
    }
}

// Marka değiştiğinde modelleri yükle ve özet güncelle - GÜNCELLENMIŞ!
document.getElementById('brand_id').addEventListener('change', function() {
    const brandId = this.value;
    const modelSelect = document.getElementById('model_id');
    const seriesSelect = document.getElementById('series_id');
    const engineSelect = document.getElementById('engine_id');
    
    // Alt seçimleri sıfırla
    seriesSelect.innerHTML = '<option value="">Önce model seçin</option>';
    seriesSelect.disabled = true;
    engineSelect.innerHTML = '<option value="">Önce seri seçin</option>';
    engineSelect.disabled = true;
    
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
                updateSummary(); // Özeti güncelle
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
        updateSummary(); // Özeti güncelle
    }
});

// Model değiştiğinde serileri yükle ve özet güncelle - GÜNCELLENMIŞ!
document.getElementById('model_id').addEventListener('change', function() {
    const modelId = this.value;
    const seriesSelect = document.getElementById('series_id');
    const engineSelect = document.getElementById('engine_id');
    
    // Engine seçimini de sıfırla
    engineSelect.innerHTML = '<option value="">Önce seri seçin</option>';
    engineSelect.disabled = true;
    
    if (modelId && isValidGUID(modelId)) {
        seriesSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        seriesSelect.disabled = true;
        
        fetch(`?get_series=1&model_id=${encodeURIComponent(modelId)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    seriesSelect.innerHTML = `<option value="">Hata: ${data.error}</option>`;
                    return;
                }
                
                seriesSelect.innerHTML = '<option value="">Seri Seçin</option>';
                data.forEach(series => {
                    seriesSelect.innerHTML += `<option value="${series.id}">${series.name}</option>`;
                });
                seriesSelect.disabled = false;
                updateSummary(); // Özeti güncelle
            })
            .catch(error => {
                seriesSelect.innerHTML = '<option value="">Bağlantı hatası</option>';
                seriesSelect.disabled = true;
            });
    } else {
        seriesSelect.innerHTML = '<option value="">Önce model seçin</option>';
        seriesSelect.disabled = true;
        updateSummary(); // Özeti güncelle
    }
});

// Series değiştiğinde motorları yükle ve özet güncelle - GÜNCELLENMIŞ!
document.getElementById('series_id').addEventListener('change', function() {
    const seriesId = this.value;
    const engineSelect = document.getElementById('engine_id');
    
    if (seriesId && isValidGUID(seriesId)) {
        engineSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        engineSelect.disabled = true;
        
        fetch(`?get_engines=1&series_id=${encodeURIComponent(seriesId)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    engineSelect.innerHTML = `<option value="">Hata: ${data.error}</option>`;
                    return;
                }
                
                engineSelect.innerHTML = '<option value="">Motor Seçin</option>';
                data.forEach(engine => {
                    engineSelect.innerHTML += `<option value="${engine.id}">${engine.name}</option>`;
                });
                engineSelect.disabled = false;
                updateSummary(); // Özeti güncelle
            })
            .catch(error => {
                engineSelect.innerHTML = '<option value="">Bağlantı hatası</option>';
                engineSelect.disabled = true;
            });
    } else {
        engineSelect.innerHTML = '<option value="">Önce seri seçin</option>';
        engineSelect.disabled = true;
        updateSummary(); // Özeti güncelle
    }
});

// Engine değiştiğinde özeti güncelle - YENİ!
document.getElementById('engine_id').addEventListener('change', function() {
    updateSummary();
});

// Dosya yükleme işlemleri
const fileInput = document.getElementById('file');
const fileUploadArea = document.getElementById('fileUploadArea');
const fileInfo = document.getElementById('fileInfo');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');

// Dosya seçim problemini çözmek için event listener'ları düzeltiyoruz
let isDragOver = false;

// Drag & Drop
fileUploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    e.stopPropagation();
    isDragOver = true;
    this.classList.add('dragover');
});

fileUploadArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    // Sadece gerçekten area'dan çıkıldığında class'ı kaldır
    setTimeout(() => {
        if (!isDragOver) {
            this.classList.remove('dragover');
        }
    }, 10);
    isDragOver = false;
});

fileUploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    e.stopPropagation();
    isDragOver = false;
    this.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(files[0]);
        fileInput.files = dataTransfer.files;
        showFileInfo(files[0]);
    }
});

// Upload area click - tüm alana tıklandığında dosya seçimi aç
fileUploadArea.addEventListener('click', function(e) {
    // Remove file button'una tıklanmadığından emin ol
    if (!e.target.classList.contains('remove-file') && !e.target.closest('.remove-file')) {
        console.log('Upload area clicked, opening file dialog');
        document.getElementById('file').click();
    }
});

// File input change - dosya seçim problemini çözen iyileştirilmiş versiyon
fileInput.addEventListener('change', function(e) {
    console.log('File input changed, files count:', this.files.length);
    
    if (this.files && this.files.length > 0) {
        const file = this.files[0];
        console.log('Selected file:', file.name, 'Size:', file.size);
        showFileInfo(file);
    } else {
        console.log('No file selected or files cleared');
        hideFileInfo();
    }
});

function showFileInfo(file) {
    if (fileName && fileSize && fileInfo) {
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileInfo.style.display = 'block';
        
        // Upload area'yı güncelle
        fileUploadArea.classList.add('file-selected');
        
        console.log('File info displayed:', file.name);
        updateSummary(); // Dosya seçimi değiştiğinde özeti güncelle
    }
}

function hideFileInfo() {
    if (fileInfo) {
        fileInfo.style.display = 'none';
        fileUploadArea.classList.remove('file-selected');
        console.log('File info hidden');
        updateSummary();
    }
}

function clearFile() {
    fileInput.value = '';
    hideFileInfo();
    console.log('File cleared');
    updateSummary(); // Dosya temizlendiğinde özeti güncelle
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Form validation - GELİŞTİRİLMİŞ VALIDATION SİSTEMİ
(function() {
    'use strict';
    window.addEventListener('load', function() {
        const forms = document.getElementsByClassName('needs-validation');
        Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                // Tüm validation hatalarını temizle
                clearAllValidationErrors();
                
                let isValid = true;
                
                // Manuel validation kontrolleri
                const requiredFields = [
                    { id: 'brand_id', name: 'Marka', isGuid: true },
                    { id: 'model_id', name: 'Model', isGuid: true },
                    { id: 'series_id', name: 'Seri', isGuid: true },
                    { id: 'engine_id', name: 'Motor', isGuid: true },
                    { id: 'plate', name: 'Plaka', isGuid: false },
                    { id: 'ecu_id', name: 'ECU Tipi', isGuid: true },
                    { id: 'device_id', name: 'Kullanılan Cihaz', isGuid: true },
                    { id: 'file', name: 'Dosya', isFile: true },
                    { id: 'notes', name: 'Notlar ve Açıklamalar', isGuid: false },
                    { id: 'terms', name: 'Şartları kabul etme', isCheckbox: true }
                ];
                
                requiredFields.forEach(field => {
                    const element = document.getElementById(field.id);
                    if (element) {
                        let value;
                        
                        if (field.isFile) {
                            value = element.files && element.files.length > 0;
                        } else if (field.isCheckbox) {
                            value = element.checked;
                        } else {
                            value = element.value ? element.value.trim() : '';
                        }
                        
                        // Boş kontrolü
                        if (!value) {
                            let message;
                            if (field.isFile) {
                                message = `${field.name} seçimi zorunludur.`;
                            } else if (field.isCheckbox) {
                                message = `${field.name} zorunludur.`;
                            } else {
                                message = `${field.name} alanı zorunludur.`;
                            }
                            showFieldError(element, message);
                            isValid = false;
                        } else {
                            // GUID kontrolü
                            if (field.isGuid && !isValidGUID(value)) {
                                showFieldError(element, `Geçersiz ${field.name} formatı.`);
                                isValid = false;
                            } else {
                                clearFieldError(element);
                            }
                        }
                    }
                });
                
                // Eğer validation başarısız ise form gönderimini engelle
                if (!isValid || form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // İlk hatalı alanı bul ve focus et
                    const firstInvalidField = form.querySelector('.is-invalid');
                    if (firstInvalidField) {
                        firstInvalidField.focus();
                        firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    
                    showError('Lütfen tüm zorunlu alanları doldurun.');
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Alan hatası gösterme fonksiyonu
function showFieldError(element, message) {
    // Mevcut hata mesajlarını temizle
    clearFieldError(element);
    
    // Element'i invalid olarak işaretle
    element.classList.add('is-invalid');
    element.classList.remove('is-valid');
    
    // Hata mesajı oluştur
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback d-block';
    errorDiv.textContent = message;
    errorDiv.setAttribute('data-validation-error', 'true');
    
    // Hata mesajını element'in sonrasına ekle
    if (element.type === 'checkbox') {
        // Checkbox için parent container'a ekle
        const parent = element.closest('.form-check') || element.parentNode;
        parent.appendChild(errorDiv);
    } else {
        element.parentNode.appendChild(errorDiv);
    }
    
    console.log('Field error shown:', message);
}

// Alan hatasını temizleme fonksiyonu
function clearFieldError(element) {
    // Invalid class'ını kaldır
    element.classList.remove('is-invalid');
    
    // Valid class ekle (boş değilse)
    if (element.value && element.value.trim()) {
        element.classList.add('is-valid');
    }
    
    // Hata mesajlarını kaldır
    let parent;
    if (element.type === 'checkbox') {
        parent = element.closest('.form-check') || element.parentNode;
    } else {
        parent = element.parentNode;
    }
    
    const errorMessages = parent.querySelectorAll('[data-validation-error="true"]');
    errorMessages.forEach(msg => msg.remove());
}

// Tüm validation hatalarını temizle
function clearAllValidationErrors() {
    // Tüm is-invalid class'larını kaldır
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    
    // Tüm validation error mesajlarını kaldır
    document.querySelectorAll('[data-validation-error="true"]').forEach(el => {
        el.remove();
    });
}

// Form elementlerini dinle - GÜNCELLENMIŞ!
document.addEventListener('DOMContentLoaded', function() {
    // Tüm form elementleri için event listener ekle
    const formInputs = document.querySelectorAll('input, select, textarea');
    formInputs.forEach(input => {
        input.addEventListener('input', updateSummary);
        input.addEventListener('change', updateSummary);
    });
    
    // Özel event listener'lar
    document.getElementById('brand_id').addEventListener('change', updateSummary);
    document.getElementById('model_id').addEventListener('change', updateSummary);
    document.getElementById('series_id').addEventListener('change', updateSummary);
    document.getElementById('engine_id').addEventListener('change', updateSummary);
    document.getElementById('ecu_id').addEventListener('change', updateSummary);
    document.getElementById('device_id').addEventListener('change', updateSummary);
    document.getElementById('plate').addEventListener('input', updateSummary);
    document.getElementById('notes').addEventListener('input', updateSummary);
    document.getElementById('notes').addEventListener('change', updateSummary);
    
    // Dosya değişikliği için özel listener
    document.getElementById('file').addEventListener('change', updateSummary);
    
    // İlk yükleme özeti
    updateSummary();
});





// Initialize
showStep(1);
</script>

<?php
// Footer include
include '../includes/user_footer.php';
?>