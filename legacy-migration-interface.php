<?php
/**
 * MR.ECU Legacy Data Migration Web Interface
 * SQL Server verilerini GUID MySQL sistemine entegre etme arayüzü
 */

require_once __DIR__ . '/config/legacy-data-migration.php';

// Database bağlantısı
try {
    $database = new Database();
    $pdo = $database->getConnection();
    $migration = new LegacyDataMigration($pdo);
} catch (Exception $e) {
    die("Database bağlantı hatası: " . $e->getMessage());
}

// POST işlemleri
$message = '';
$messageType = '';

if ($_POST) {
    switch ($_POST['action']) {
        case 'add_missing_columns':
            $result = $migration->addMissingColumns();
            $message = $result ? "Eksik alanlar başarıyla eklendi!" : "Eksik alan ekleme işleminde hata oluştu!";
            $messageType = $result ? 'success' : 'danger';
            break;
            
        case 'create_legacy_tables':
            $result = $migration->createLegacyTables();
            $message = $result ? "Legacy tablolar başarıyla oluşturuldu!" : "Legacy tablo oluşturma işleminde hata oluştu!";
            $messageType = $result ? 'success' : 'danger';
            break;
            
        case 'create_mapping_tables':
            $result = $migration->createMappingTables();
            $message = $result ? "Mapping tabloları başarıyla oluşturuldu!" : "Mapping tablo oluşturma işleminde hata oluştu!";
            $messageType = $result ? 'success' : 'danger';
            break;
            
        case 'cleanup':
            $result = $migration->cleanup();
            $message = $result ? "Temizlik işlemi başarıyla tamamlandı!" : "Temizlik işleminde hata oluştu!";
            $messageType = $result ? 'success' : 'warning';
            break;
    }
}

// İstatistikleri al
$stats = $migration->getStats();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MR.ECU Legacy Data Migration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #011b8f 0%, #ab0000 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            padding: 30px 0;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.95);
        }
        
        .card-header {
            background: linear-gradient(135deg, #071e3d, #d32835);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 25px;
        }
        
        .btn-action {
            margin: 5px;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin: 10px 0;
            text-align: center;
        }
        
        .log-container {
            background: #1e1e1e;
            color: #00ff00;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            height: 400px;
            overflow-y: auto;
        }
        
        .log-entry {
            margin: 2px 0;
            font-size: 14px;
        }
        
        .progress-container {
            margin: 20px 0;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            margin: 0 5px;
            border-radius: 10px;
            position: relative;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .step.active {
            background: #007bff;
            color: white;
        }
        
        .upload-zone {
            border: 2px dashed #007bff;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            margin: 20px 0;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-zone:hover {
            background: #e9ecef;
            border-color: #0056b3;
        }
        
        .upload-zone.dragover {
            background: #e7f3ff;
            border-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="mb-0">
                            <i class="fas fa-database"></i> 
                            MR.ECU Legacy Data Migration System
                        </h2>
                        <p class="mb-0 mt-2">SQL Server verilerini GUID MySQL sistemine entegre etme</p>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <!-- İstatistikler -->
                        <div class="row mb-4">
                            <div class="col-md-2">
                                <div class="stats-card">
                                    <h4><?= $stats['total_users'] ?? 0 ?></h4>
                                    <small>Kullanıcılar</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stats-card">
                                    <h4><?= $stats['total_admins'] ?? 0 ?></h4>
                                    <small>Adminler</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stats-card">
                                    <h4><?= $stats['total_files'] ?? 0 ?></h4>
                                    <small>Dosyalar</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stats-card">
                                    <h4><?= number_format($stats['total_credits'] ?? 0, 2) ?></h4>
                                    <small>Toplam Kredi</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stats-card">
                                    <h4><?= $stats['total_brands'] ?? 0 ?></h4>
                                    <small>Markalar</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="stats-card">
                                    <h4><?= $stats['total_models'] ?? 0 ?></h4>
                                    <small>Modeller</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Migration Adımları -->
                        <div class="step-indicator">
                            <div class="step">
                                <i class="fas fa-database fa-2x mb-2"></i>
                                <h6>1. Schema Hazırlık</h6>
                                <small>Eksik alanlar ve tablolar</small>
                            </div>
                            <div class="step">
                                <i class="fas fa-exchange-alt fa-2x mb-2"></i>
                                <h6>2. Mapping Tabloları</h6>
                                <small>Brand/Model/User mapping</small>
                            </div>
                            <div class="step">
                                <i class="fas fa-upload fa-2x mb-2"></i>
                                <h6>3. Veri Import</h6>
                                <small>CSV dosyalarından veri aktarımı</small>
                            </div>
                            <div class="step">
                                <i class="fas fa-check fa-2x mb-2"></i>
                                <h6>4. Kontrol & Temizlik</h6>
                                <small>Doğrulama ve temizlik</small>
                            </div>
                        </div>
                        
                        <!-- Schema Hazırlık -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5><i class="fas fa-database"></i> 1. Schema Hazırlık</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>GUID MySQL veritabanına eksik alanları ve legacy tabloları ekler.</p>
                                        
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="add_missing_columns">
                                            <button type="submit" class="btn btn-primary btn-action">
                                                <i class="fas fa-plus"></i> Eksik Alanları Ekle
                                            </button>
                                        </form>
                                        
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="create_legacy_tables">
                                            <button type="submit" class="btn btn-info btn-action">
                                                <i class="fas fa-table"></i> Legacy Tabloları Oluştur
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-warning text-dark">
                                        <h5><i class="fas fa-exchange-alt"></i> 2. Mapping Hazırlık</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Brand, Model ve User mapping tabloları oluşturur.</p>
                                        
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="create_mapping_tables">
                                            <button type="submit" class="btn btn-warning btn-action">
                                                <i class="fas fa-sitemap"></i> Mapping Tabloları Oluştur
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Veri Import Bölümü -->
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5><i class="fas fa-upload"></i> 3. Veri Import</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <h6>Users CSV Upload</h6>
                                        <div class="upload-zone" onclick="document.getElementById('usersFile').click()">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <p>Users CSV dosyasını sürükleyip bırakın veya tıklayın</p>
                                            <input type="file" id="usersFile" accept=".csv" style="display: none;">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <h6>Files CSV Upload</h6>
                                        <div class="upload-zone" onclick="document.getElementById('filesFile').click()">
                                            <i class="fas fa-file fa-3x text-muted mb-3"></i>
                                            <p>Files CSV dosyasını sürükleyip bırakın veya tıklayın</p>
                                            <input type="file" id="filesFile" accept=".csv" style="display: none;">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <h6>Tickets CSV Upload</h6>
                                        <div class="upload-zone" onclick="document.getElementById('ticketsFile').click()">
                                            <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                            <p>Tickets CSV dosyasını sürükleyip bırakın veya tıklayın</p>
                                            <input type="file" id="ticketsFile" accept=".csv" style="display: none;">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <h6>Wallet Log CSV Upload</h6>
                                        <div class="upload-zone" onclick="document.getElementById('walletFile').click()">
                                            <i class="fas fa-wallet fa-3x text-muted mb-3"></i>
                                            <p>Wallet Log CSV dosyasını sürükleyip bırakın veya tıklayın</p>
                                            <input type="file" id="walletFile" accept=".csv" style="display: none;">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="progress-container" style="display: none;">
                                    <h6>Import Progress</h6>
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-success btn-lg" id="startImport" disabled>
                                    <i class="fas fa-play"></i> Import İşlemini Başlat
                                </button>
                            </div>
                        </div>
                        
                        <!-- Kontrol ve Temizlik -->
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white">
                                <h5><i class="fas fa-broom"></i> 4. Kontrol & Temizlik</h5>
                            </div>
                            <div class="card-body">
                                <p>Migration işlemi tamamlandıktan sonra geçici tabloları temizler.</p>
                                
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="cleanup">
                                    <button type="submit" class="btn btn-secondary btn-action">
                                        <i class="fas fa-broom"></i> Geçici Tabloları Temizle
                                    </button>
                                </form>
                                
                                <a href="legacy-data-converter.php" class="btn btn-info btn-action">
                                    <i class="fas fa-exchange-alt"></i> SQL Server Data Converter
                                </a>
                                
                                <a href="../final-guid-migration-complete.php" class="btn btn-primary btn-action">
                                    <i class="fas fa-check-circle"></i> GUID System Test
                                </a>
                            </div>
                        </div>
                        
                        <!-- Log Konsolu -->
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <h5><i class="fas fa-terminal"></i> Migration Logları</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="log-container" id="logContainer">
                                    <div class="log-entry">[<?= date('Y-m-d H:i:s') ?>] Migration sistemi hazır...</div>
                                    <div class="log-entry">[<?= date('Y-m-d H:i:s') ?>] Komutları çalıştırmak için yukarıdaki butonları kullanın.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload handlers
        const fileInputs = ['usersFile', 'filesFile', 'ticketsFile', 'walletFile'];
        let uploadedFiles = {};
        
        fileInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    uploadedFiles[inputId] = file;
                    updateUploadStatus();
                    logMessage(`${file.name} dosyası seçildi (${inputId})`);
                }
            });
        });
        
        // Drag and drop
        document.querySelectorAll('.upload-zone').forEach(zone => {
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            
            zone.addEventListener('dragleave', function(e) {
                this.classList.remove('dragover');
            });
            
            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const inputId = this.onclick.toString().match(/getElementById\('(\w+)'\)/)[1];
                    document.getElementById(inputId).files = files;
                    uploadedFiles[inputId] = files[0];
                    updateUploadStatus();
                    logMessage(`${files[0].name} dosyası yüklendi (${inputId})`);
                }
            });
        });
        
        function updateUploadStatus() {
            const startButton = document.getElementById('startImport');
            const uploadedCount = Object.keys(uploadedFiles).length;
            
            if (uploadedCount > 0) {
                startButton.disabled = false;
                startButton.innerHTML = `<i class="fas fa-play"></i> Import İşlemini Başlat (${uploadedCount} dosya hazır)`;
            }
        }
        
        function logMessage(message) {
            const logContainer = document.getElementById('logContainer');
            const timestamp = new Date().toLocaleString();
            const logEntry = document.createElement('div');
            logEntry.className = 'log-entry';
            logEntry.textContent = `[${timestamp}] ${message}`;
            logContainer.appendChild(logEntry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }
        
        // Migration hazırlığı
        function prepareMigration() {
            logMessage('Migration hazırlığı başlatılıyor...');
            
            fetch('ajax-migration-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=prepare_migration'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    logMessage('✓ Migration hazırlığı tamamlandı');
                    createMappings();
                } else {
                    logMessage('❌ Migration hazırlığı hatası: ' + data.message);
                }
            })
            .catch(error => {
                logMessage('❌ Network hatası: ' + error);
            });
        }
        
        function createMappings() {
            logMessage('Brand mapping oluşturuluyor...');
            
            fetch('ajax-migration-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=create_brand_mapping'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    logMessage('✓ ' + data.message);
                    createModelMapping();
                } else {
                    logMessage('❌ Brand mapping hatası: ' + data.message);
                }
            });
        }
        
        function createModelMapping() {
            logMessage('Model mapping oluşturuluyor...');
            
            fetch('ajax-migration-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=create_model_mapping'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    logMessage('✓ ' + data.message);
                    logMessage('🎉 Sistem import için hazır!');
                    document.getElementById('startImport').disabled = false;
                    document.getElementById('startImport').innerHTML = '<i class="fas fa-play"></i> Import İşlemini Başlat';
                } else {
                    logMessage('❌ Model mapping hatası: ' + data.message);
                }
            });
        }
        
        // Import işlemi başlatma
        document.getElementById('startImport').addEventListener('click', function() {
            if (Object.keys(uploadedFiles).length === 0) {
                alert('Lütfen en az bir CSV dosyası seçin!');
                return;
            }
            
            logMessage('Import işlemi başlatılıyor...');
            this.disabled = true;
            
            // Önce hazırlık yap
            prepareMigration();
            
            // Sonra dosyaları import et
            setTimeout(() => {
                importFiles();
            }, 3000);
        });
        
        function importFiles() {
            const progressContainer = document.querySelector('.progress-container');
            const progressBar = document.querySelector('.progress-bar');
            progressContainer.style.display = 'block';
            
            let currentStep = 0;
            const steps = Object.keys(uploadedFiles);
            const totalSteps = steps.length;
            
            function importNextFile() {
                if (currentStep >= totalSteps) {
                    // Tüm dosyalar import edildi
                    progressBar.style.width = '100%';
                    progressBar.textContent = '100%';
                    logMessage('🎉 Tüm dosyalar başarıyla import edildi!');
                    
                    // İstatistikleri güncelle
                    updateStats();
                    
                    document.getElementById('startImport').disabled = false;
                    document.getElementById('startImport').innerHTML = '<i class="fas fa-check"></i> Import Tamamlandı';
                    return;
                }
                
                const fileInputId = steps[currentStep];
                const file = uploadedFiles[fileInputId];
                const progress = ((currentStep + 1) / totalSteps) * 100;
                
                progressBar.style.width = progress + '%';
                progressBar.textContent = Math.round(progress) + '%';
                
                logMessage(`Import ediliyor: ${file.name}`);
                
                // Dosyayı import et
                const formData = new FormData();
                formData.append('csv_file', file);
                
                let action = 'import_users';
                if (fileInputId === 'filesFile') action = 'import_files';
                else if (fileInputId === 'ticketsFile') action = 'import_tickets';
                else if (fileInputId === 'walletFile') action = 'import_wallet_log';
                
                formData.append('action', action);
                
                fetch('ajax-migration-handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        logMessage(`✓ ${file.name} başarıyla import edildi`);
                        if (data.data) {
                            logMessage(`  - İmport edilen: ${data.data.imported || 0}`);
                            logMessage(`  - Atlanan: ${data.data.skipped || 0}`);
                        }
                    } else {
                        logMessage(`❌ ${file.name} import hatası: ${data.message}`);
                    }
                    
                    currentStep++;
                    setTimeout(importNextFile, 1000);
                })
                .catch(error => {
                    logMessage(`❌ ${file.name} network hatası: ${error}`);
                    currentStep++;
                    setTimeout(importNextFile, 1000);
                });
            }
            
            importNextFile();
        }
        
        function updateStats() {
            fetch('ajax-migration-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_stats'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    // İstatistik kutularını güncelle
                    const stats = data.data;
                    document.querySelector('.stats-card:nth-child(1) h4').textContent = stats.total_users || 0;
                    document.querySelector('.stats-card:nth-child(2) h4').textContent = stats.total_admins || 0;
                    document.querySelector('.stats-card:nth-child(3) h4').textContent = stats.total_files || 0;
                    document.querySelector('.stats-card:nth-child(4) h4').textContent = (stats.total_credits || 0).toFixed(2);
                    document.querySelector('.stats-card:nth-child(5) h4').textContent = stats.total_brands || 0;
                    document.querySelector('.stats-card:nth-child(6) h4').textContent = stats.total_models || 0;
                    
                    logMessage('📊 İstatistikler güncellendi');
                }
            });
        }
        
        // Log auto-scroll
        setInterval(function() {
            const logContainer = document.getElementById('logContainer');
            logContainer.scrollTop = logContainer.scrollHeight;
        }, 1000);
    </script>
</body>
</html>