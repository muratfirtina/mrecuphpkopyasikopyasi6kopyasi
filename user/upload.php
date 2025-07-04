<?php
/**
 * Mr ECU - Dosya Yükleme Sayfası (GUID System)
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php?redirect=user/upload.php');
}

$user = new User($pdo);
$fileManager = new FileManager($pdo);

// Session'daki kredi bilgisini güncelle
$_SESSION['credits'] = $user->getUserCredits($_SESSION['user_id']);
$error = '';
$success = '';

// Araç markalarını getir
$brands = $fileManager->getBrands();

// Ajax ile model getirme
if (isset($_GET['get_models']) && isset($_GET['brand_id'])) {
    header('Content-Type: application/json');
    $brandId = sanitize($_GET['brand_id']);
    
    // GUID format kontrolü
    if (!isValidUUID($brandId)) {
        echo json_encode(['error' => 'Geçersiz marka ID formatı']);
        exit;
    }
    
    $models = $fileManager->getModelsByBrand($brandId);
    echo json_encode($models);
    exit;
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
            'ecu_type' => sanitize($_POST['ecu_type']),
            'engine_code' => sanitize($_POST['engine_code']),
            'gearbox_type' => sanitize($_POST['gearbox_type']),
            'fuel_type' => sanitize($_POST['fuel_type']),
            'hp_power' => !empty($_POST['hp_power']) ? (int)$_POST['hp_power'] : null,
            'nm_torque' => !empty($_POST['nm_torque']) ? (int)$_POST['nm_torque'] : null
        ];
        
        $notes = sanitize($_POST['notes']);
        
        // Validation
        if ($vehicleData['year'] < 1990 || $vehicleData['year'] > date('Y') + 1) {
            $error = 'Geçerli bir model yılı girin.';
        } else {
            $result = $fileManager->uploadFile($_SESSION['user_id'], $_FILES['file'], $vehicleData, $notes);
            
            if ($result['success']) {
                $success = $result['message'];
                // Form verilerini temizle
                $_POST = [];
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pageTitle = 'Dosya Yükle';
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
                        <i class="fas fa-upload me-2"></i>Dosya Yükle
                        <small class="text-muted">(GUID System)</small>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="files.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-folder me-1"></i>Dosyalarım
                            </a>
                        </div>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <div class="mt-2">
                            <a href="files.php" class="btn btn-success btn-sm">
                                <i class="fas fa-folder me-1"></i>Dosyalarımı Görüntüle
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-car me-2"></i>Araç Bilgileri ve Dosya Yükleme
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                    <!-- Araç Bilgileri -->
                                    <h6 class="text-muted mb-3">
                                        <i class="fas fa-info-circle me-1"></i>Araç Bilgileri
                                    </h6>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="brand_id" class="form-label">Marka *</label>
                                            <select class="form-select" id="brand_id" name="brand_id" required>
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
                                            <small class="text-muted">GUID ID kullanılıyor</small>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="model_id" class="form-label">Model *</label>
                                            <select class="form-select" id="model_id" name="model_id" required disabled>
                                                <option value="">Önce marka seçin</option>
                                            </select>
                                            <div class="invalid-feedback">Model seçimi zorunludur.</div>
                                            <small class="text-muted">GUID ID kullanılıyor</small>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="year" class="form-label">Model Yılı *</label>
                                            <input type="number" class="form-control" id="year" name="year" 
                                                   min="1990" max="<?php echo date('Y') + 1; ?>" 
                                                   value="<?php echo isset($_POST['year']) ? $_POST['year'] : ''; ?>" required>
                                            <div class="invalid-feedback">Geçerli bir model yılı girin.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="ecu_type" class="form-label">ECU Tipi</label>
                                            <input type="text" class="form-control" id="ecu_type" name="ecu_type" 
                                                   placeholder="Örn: Bosch ME7.5" 
                                                   value="<?php echo isset($_POST['ecu_type']) ? htmlspecialchars($_POST['ecu_type']) : ''; ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="engine_code" class="form-label">Motor Kodu</label>
                                            <input type="text" class="form-control" id="engine_code" name="engine_code" 
                                                   placeholder="Örn: AWT, AWP" 
                                                   value="<?php echo isset($_POST['engine_code']) ? htmlspecialchars($_POST['engine_code']) : ''; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="gearbox_type" class="form-label">Şanzıman</label>
                                            <select class="form-select" id="gearbox_type" name="gearbox_type">
                                                <option value="Manual" <?php echo (isset($_POST['gearbox_type']) && $_POST['gearbox_type'] === 'Manual') ? 'selected' : ''; ?>>Manuel</option>
                                                <option value="Automatic" <?php echo (isset($_POST['gearbox_type']) && $_POST['gearbox_type'] === 'Automatic') ? 'selected' : ''; ?>>Otomatik</option>
                                                <option value="CVT" <?php echo (isset($_POST['gearbox_type']) && $_POST['gearbox_type'] === 'CVT') ? 'selected' : ''; ?>>CVT</option>
                                                <option value="DSG" <?php echo (isset($_POST['gearbox_type']) && $_POST['gearbox_type'] === 'DSG') ? 'selected' : ''; ?>>DSG</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="fuel_type" class="form-label">Yakıt Türü</label>
                                            <select class="form-select" id="fuel_type" name="fuel_type">
                                                <option value="Benzin" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type'] === 'Benzin') ? 'selected' : ''; ?>>Benzin</option>
                                                <option value="Dizel" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type'] === 'Dizel') ? 'selected' : ''; ?>>Dizel</option>
                                                <option value="LPG" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type'] === 'LPG') ? 'selected' : ''; ?>>LPG</option>
                                                <option value="Hybrid" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type'] === 'Hybrid') ? 'selected' : ''; ?>>Hibrit</option>
                                                <option value="Electric" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type'] === 'Electric') ? 'selected' : ''; ?>>Elektrik</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="hp_power" class="form-label">Güç (HP)</label>
                                            <input type="number" class="form-control" id="hp_power" name="hp_power" 
                                                   min="1" max="2000" placeholder="150" 
                                                   value="<?php echo isset($_POST['hp_power']) ? $_POST['hp_power'] : ''; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nm_torque" class="form-label">Tork (Nm)</label>
                                            <input type="number" class="form-control" id="nm_torque" name="nm_torque" 
                                                   min="1" max="5000" placeholder="320" 
                                                   value="<?php echo isset($_POST['nm_torque']) ? $_POST['nm_torque'] : ''; ?>">
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <!-- Dosya Yükleme -->
                                    <h6 class="text-muted mb-3">
                                        <i class="fas fa-file me-1"></i>Dosya Yükleme
                                    </h6>
                                    
                                    <div class="mb-3">
                                        <label for="file" class="form-label">ECU Dosyası *</label>
                                        <div class="file-upload-area" id="fileUploadArea">
                                            <input type="file" class="form-control" id="file" name="file" 
                                                   accept=".bin,.hex,.ecu,.ori,.mod,.zip,.rar" required style="display: none;">
                                            <div class="upload-content">
                                                <i class="fas fa-cloud-upload-alt text-primary" style="font-size: 3rem;"></i>
                                                <h5 class="mt-3">Dosyanızı buraya sürükleyin</h5>
                                                <p class="text-muted">veya <button type="button" class="btn btn-link p-0" onclick="document.getElementById('file').click()">buraya tıklayın</button></p>
                                                <small class="text-muted">
                                                    Desteklenen formatlar: <?php echo implode(', ', ALLOWED_EXTENSIONS); ?><br>
                                                    Maksimum dosya boyutu: <?php echo (MAX_FILE_SIZE / 1024 / 1024); ?>MB
                                                </small>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback">Dosya seçimi zorunludur.</div>
                                    </div>
                                    
                                    <div class="mb-3" id="fileInfo" style="display: none;">
                                        <div class="alert alert-info">
                                            <i class="fas fa-file me-2"></i>
                                            <span id="fileName"></span>
                                            <span class="float-end">
                                                <span id="fileSize"></span>
                                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearFile()">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Notlar</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                                  placeholder="Dosya hakkında özel notlarınız..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-upload me-2"></i>Dosyayı Yükle
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Bilgilendirme -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Önemli Bilgiler
                                </h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Dosyalarınız güvenli şekilde şifrelenir
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        24-48 saat içinde işlenir
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Email ile bilgilendirilirsiniz
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>
                                        Sadece siz erişebilirsiniz
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-shield-alt text-primary me-2"></i>
                                        GUID ID sistemi ile güvenlik
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Desteklenen Formatlar -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-file-code me-2"></i>Desteklenen Dosya Formatları
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach (ALLOWED_EXTENSIONS as $ext): ?>
                                        <div class="col-6 mb-2">
                                            <span class="badge bg-light text-dark">.<?php echo $ext; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // GUID validation function
        function isValidGUID(guid) {
            const guidPattern = /^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
            return guidPattern.test(guid);
        }
        
        // Marka değiştiğinde modelleri yükle
        document.getElementById('brand_id').addEventListener('change', function() {
            const brandId = this.value;
            const modelSelect = document.getElementById('model_id');
            
            if (brandId && isValidGUID(brandId)) {
                fetch(`?get_models=1&brand_id=${encodeURIComponent(brandId)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Server error:', data.error);
                            modelSelect.innerHTML = '<option value="">Hata oluştu</option>';
                            return;
                        }
                        
                        modelSelect.innerHTML = '<option value="">Model Seçin</option>';
                        data.forEach(model => {
                            const displayName = model.display_name || model.name;
                            modelSelect.innerHTML += `<option value="${model.id}" data-guid="${model.id}">${displayName}</option>`;
                        });
                        modelSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        modelSelect.innerHTML = '<option value="">Hata oluştu</option>';
                    });
            } else if (brandId && !isValidGUID(brandId)) {
                console.error('Invalid GUID format for brand:', brandId);
                modelSelect.innerHTML = '<option value="">Geçersiz marka ID</option>';
                modelSelect.disabled = true;
            } else {
                modelSelect.innerHTML = '<option value="">Önce marka seçin</option>';
                modelSelect.disabled = true;
            }
        });

        // Dosya yükleme alanı
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

        // Form validation with GUID checks
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
                            alert('Geçersiz marka GUID formatı: ' + brandId);
                            return false;
                        }
                        
                        if (modelId && !isValidGUID(modelId)) {
                            event.preventDefault();
                            event.stopPropagation();
                            alert('Geçersiz model GUID formatı: ' + modelId);
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
    </script>
</body>
</html>
