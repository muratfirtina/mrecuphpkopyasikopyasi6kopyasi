<?php
/**
 * MR.ECU Sample Data Generator
 * Test için örnek legacy verileri oluşturur
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// CSV dosyalarını oluşturmak için sample data
class SampleDataGenerator {
    private $outputDir;
    
    public function __construct() {
        $this->outputDir = __DIR__ . '/sample_data/';
        if (!file_exists($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    // Sample Users data
    public function generateUsersCSV() {
        $users = [
            [
                'new_id' => generateUUID(),
                'legacy_id' => 'user1-legacy-id',
                'username' => 'testuser1',
                'email' => 'user1@test.com',
                'password' => password_hash('123456', PASSWORD_DEFAULT),
                'first_name' => 'Test',
                'last_name' => 'User 1',
                'phone' => '05551234567',
                'wallet' => 150.00,
                'user_type' => 'user',
                'is_confirm' => 1,
                'created_date' => '2024-01-15 10:30:00',
                'updated_date' => '2024-01-15 10:30:00',
                'deleted_date' => null
            ],
            [
                'new_id' => generateUUID(),
                'legacy_id' => 'user2-legacy-id',
                'username' => 'testuser2',
                'email' => 'user2@test.com',
                'password' => password_hash('123456', PASSWORD_DEFAULT),
                'first_name' => 'Test',
                'last_name' => 'User 2',
                'phone' => '05551234568',
                'wallet' => 250.00,
                'user_type' => 'user',
                'is_confirm' => 1,
                'created_date' => '2024-02-10 14:20:00',
                'updated_date' => '2024-02-10 14:20:00',
                'deleted_date' => null
            ],
            [
                'new_id' => generateUUID(),
                'legacy_id' => 'admin1-legacy-id',
                'username' => 'testadmin',
                'email' => 'admin@test.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'first_name' => 'Test',
                'last_name' => 'Admin',
                'phone' => '05551234569',
                'wallet' => 0.00,
                'user_type' => 'admin',
                'is_confirm' => 1,
                'created_date' => '2024-01-01 09:00:00',
                'updated_date' => '2024-01-01 09:00:00',
                'deleted_date' => null
            ]
        ];
        
        $this->writeCSV($users, 'sample_users.csv');
        return count($users);
    }
    
    // Sample Files data
    public function generateFilesCSV() {
        $files = [
            [
                'new_id' => generateUUID(),
                'legacy_file_id' => 'file1-legacy-id',
                'user_id' => 'user1-legacy-id',
                'brand' => 'Audi',
                'model' => 'A3',
                'year' => 2015,
                'ecu' => 'Bosch EDC17',
                'motor' => '2.0 TDI',
                'device_type' => 'KESS V2',
                'gearbox_type' => 'Manual',
                'fuel_type' => 'Dizel',
                'kilometer' => '125000',
                'plate' => '34ABC123',
                'type' => 'Stage 1',
                'file_link' => 'uploads/2024/01/sample_file1.bin',
                'comment' => 'Stage 1 tuning talebi',
                'code' => 'A3-2015-001',
                'status' => 2,
                'status_text' => 'Completed',
                'admin_note' => 'Başarıyla tamamlandı',
                'price' => 25.00,
                'updated_file_link' => 'uploads/2024/01/sample_file1_tuned.bin',
                'created_date' => '2024-01-16 11:00:00'
            ],
            [
                'new_id' => generateUUID(),
                'legacy_file_id' => 'file2-legacy-id',
                'user_id' => 'user2-legacy-id',
                'brand' => 'BMW',
                'model' => '320d',
                'year' => 2018,
                'ecu' => 'Bosch MG1',
                'motor' => '2.0d',
                'device_type' => 'KESS3',
                'gearbox_type' => 'Automatic',
                'fuel_type' => 'Dizel',
                'kilometer' => '85000',
                'plate' => '06XYZ789',
                'type' => 'DPF Delete',
                'file_link' => 'uploads/2024/02/sample_file2.bin',
                'comment' => 'DPF silme işlemi',
                'code' => 'BMW-2018-002',
                'status' => 1,
                'status_text' => 'Processing',
                'admin_note' => 'İşlem devam ediyor',
                'price' => 35.00,
                'updated_file_link' => null,
                'created_date' => '2024-02-11 15:30:00'
            ],
            [
                'new_id' => generateUUID(),
                'legacy_file_id' => 'file3-legacy-id',
                'user_id' => 'user1-legacy-id',
                'brand' => 'Volkswagen',
                'model' => 'Golf 7',
                'year' => 2016,
                'ecu' => 'Continental SIMOS',
                'motor' => '1.6 TDI',
                'device_type' => 'AutoTuner',
                'gearbox_type' => 'Manual',
                'fuel_type' => 'Dizel',
                'kilometer' => '95000',
                'plate' => '35DEF456',
                'type' => 'Stage 2',
                'file_link' => 'uploads/2024/01/sample_file3.bin',
                'comment' => 'Stage 2 + EGR delete',
                'code' => 'VW-2016-003',
                'status' => 0,
                'status_text' => 'Pending',
                'admin_note' => '',
                'price' => 45.00,
                'updated_file_link' => null,
                'created_date' => '2024-01-20 09:15:00'
            ]
        ];
        
        $this->writeCSV($files, 'sample_files.csv');
        return count($files);
    }
    
    // Sample Tickets data
    public function generateTicketsCSV() {
        $tickets = [
            [
                'new_id' => generateUUID(),
                'legacy_ticket_id' => 'ticket1-legacy-id',
                'title' => 'Dosya problemi',
                'user_id' => 'user1-legacy-id',
                'file_id' => 'file1-legacy-id',
                'status' => 2,
                'status_text' => 'Resolved',
                'ticket_code' => 'TK-2024-001',
                'created_date' => '2024-01-17 10:00:00',
                'updated_date' => '2024-01-17 16:30:00'
            ],
            [
                'new_id' => generateUUID(),
                'legacy_ticket_id' => 'ticket2-legacy-id',
                'title' => 'Revizyon talebi',
                'user_id' => 'user2-legacy-id',
                'file_id' => 'file2-legacy-id',
                'status' => 1,
                'status_text' => 'In Progress',
                'ticket_code' => 'TK-2024-002',
                'created_date' => '2024-02-12 14:00:00',
                'updated_date' => '2024-02-12 14:00:00'
            ]
        ];
        
        $this->writeCSV($tickets, 'sample_tickets.csv');
        return count($tickets);
    }
    
    // Sample Wallet Log data
    public function generateWalletLogCSV() {
        $walletLogs = [
            [
                'new_id' => generateUUID(),
                'user_id' => 'user1-legacy-id',
                'amount' => 100.00,
                'type' => 'deposit',
                'description' => 'İlk yükleme',
                'created_at' => '2024-01-15 10:35:00'
            ],
            [
                'new_id' => generateUUID(),
                'user_id' => 'user1-legacy-id',
                'amount' => 25.00,
                'type' => 'withdraw',
                'description' => 'Dosya işleme ücreti',
                'created_at' => '2024-01-16 11:30:00'
            ],
            [
                'new_id' => generateUUID(),
                'user_id' => 'user2-legacy-id',
                'amount' => 250.00,
                'type' => 'deposit',
                'description' => 'Kredi yüklemesi',
                'created_at' => '2024-02-10 14:25:00'
            ]
        ];
        
        $this->writeCSV($walletLogs, 'sample_wallet_log.csv');
        return count($walletLogs);
    }
    
    // CSV dosyası yazma
    private function writeCSV($data, $filename) {
        $filepath = $this->outputDir . $filename;
        $file = fopen($filepath, 'w');
        
        // BOM ekleme (Excel için)
        fwrite($file, "\xEF\xBB\xBF");
        
        if (!empty($data)) {
            // Header satırı
            fputcsv($file, array_keys($data[0]));
            
            // Veri satırları
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
        }
        
        fclose($file);
    }
    
    // Tüm sample dataları oluştur
    public function generateAll() {
        $stats = [
            'users' => $this->generateUsersCSV(),
            'files' => $this->generateFilesCSV(),
            'tickets' => $this->generateTicketsCSV(),
            'wallet_logs' => $this->generateWalletLogCSV()
        ];
        
        return $stats;
    }
    
    // Sample dataları temizle
    public function cleanup() {
        $files = glob($this->outputDir . '*.csv');
        foreach ($files as $file) {
            unlink($file);
        }
        
        if (is_dir($this->outputDir)) {
            rmdir($this->outputDir);
        }
    }
}

// Web interface
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MR.ECU Sample Data Generator</title>
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
        
        .file-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .file-item:last-child {
            border-bottom: none;
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
                            MR.ECU Sample Data Generator
                        </h2>
                        <p class="mb-0 mt-2">Legacy migration test için örnek veriler oluşturur</p>
                    </div>
                    
                    <div class="card-body">
                        <?php
                        $message = '';
                        $messageType = '';
                        $stats = [];
                        
                        if (isset($_POST['action'])) {
                            $generator = new SampleDataGenerator();
                            
                            switch ($_POST['action']) {
                                case 'generate':
                                    $stats = $generator->generateAll();
                                    $message = "Sample data başarıyla oluşturuldu!";
                                    $messageType = 'success';
                                    break;
                                    
                                case 'cleanup':
                                    $generator->cleanup();
                                    $message = "Sample data temizlendi!";
                                    $messageType = 'warning';
                                    break;
                            }
                        }
                        
                        if ($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($stats)): ?>
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-primary text-white rounded">
                                        <h4><?= $stats['users'] ?></h4>
                                        <small>Users</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-success text-white rounded">
                                        <h4><?= $stats['files'] ?></h4>
                                        <small>Files</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-warning text-white rounded">
                                        <h4><?= $stats['tickets'] ?></h4>
                                        <small>Tickets</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center p-3 bg-info text-white rounded">
                                        <h4><?= $stats['wallet_logs'] ?></h4>
                                        <small>Wallet Logs</small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5><i class="fas fa-cogs"></i> İşlemler</h5>
                                <p>Test için gerekli CSV dosyalarını oluşturun veya temizleyin.</p>
                                
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="generate">
                                    <button type="submit" class="btn btn-primary btn-action">
                                        <i class="fas fa-plus"></i> Sample Data Oluştur
                                    </button>
                                </form>
                                
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="cleanup">
                                    <button type="submit" class="btn btn-warning btn-action">
                                        <i class="fas fa-trash"></i> Sample Data Temizle
                                    </button>
                                </form>
                            </div>
                            
                            <div class="col-md-6">
                                <h5><i class="fas fa-files-o"></i> Oluşturulan Dosyalar</h5>
                                <div class="file-list">
                                    <?php
                                    $sampleDir = __DIR__ . '/sample_data/';
                                    if (is_dir($sampleDir)) {
                                        $files = glob($sampleDir . '*.csv');
                                        if (!empty($files)) {
                                            foreach ($files as $file) {
                                                echo '<div class="file-item">';
                                                echo '<span>';
                                                echo '<i class="fas fa-file-csv text-success"></i> ';
                                                echo basename($file);
                                                echo '</span>';
                                                echo '<span class="text-muted">';
                                                echo function_exists('formatFileSize') ? formatFileSize(filesize($file)) : number_format(filesize($file)) . ' bytes';
                                                echo '</span>';
                                                echo '</div>';
                                            }
                                        } else {
                                            echo '<div class="text-center text-muted py-3">';
                                            echo '<i class="fas fa-folder-open fa-3x mb-3"></i>';
                                            echo '<p>Henüz oluşturulmuş dosya yok</p>';
                                            echo '</div>';
                                        }
                                    } else {
                                        echo '<div class="text-center text-muted py-3">';
                                        echo '<i class="fas fa-folder fa-3x mb-3"></i>';
                                        echo '<p>Sample data klasörü bulunamadı</p>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-4">
                            <div class="card-header bg-info text-white">
                                <h5><i class="fas fa-info-circle"></i> Sample Data İçeriği</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Users CSV:</h6>
                                        <ul>
                                            <li>2 test kullanıcısı (user1, user2)</li>
                                            <li>1 admin kullanıcısı (testadmin)</li>
                                            <li>Farklı kredi bakiyeleri</li>
                                            <li>Geçerli email ve telefon bilgileri</li>
                                        </ul>
                                        
                                        <h6>Files CSV:</h6>
                                        <ul>
                                            <li>3 farklı araç dosyası</li>
                                            <li>Audi A3, BMW 320d, VW Golf 7</li>
                                            <li>Farklı status durumları</li>
                                            <li>ECU tipleri ve tuning bilgileri</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Tickets CSV:</h6>
                                        <ul>
                                            <li>2 destek talebi</li>
                                            <li>Farklı durumlar (çözüldü, devam ediyor)</li>
                                            <li>Dosyalarla ilişkili</li>
                                        </ul>
                                        
                                        <h6>Wallet Log CSV:</h6>
                                        <ul>
                                            <li>Kredi yükleme işlemleri</li>
                                            <li>Dosya işleme ücret kesintileri</li>
                                            <li>Tarih sıralı işlemler</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h5><i class="fas fa-arrow-right"></i> Sonraki Adımlar</h5>
                            <ol>
                                <li><strong>Sample Data Oluştur:</strong> Yukarıdaki butona tıklayarak test verilerini oluşturun</li>
                                <li><strong>Migration Interface:</strong> Legacy Migration arayüzüne geçin</li>
                                <li><strong>CSV Yükleme:</strong> Oluşturulan CSV dosyalarını yükleyin</li>
                                <li><strong>Import Test:</strong> Verilerin doğru import edildiğini kontrol edin</li>
                            </ol>
                            
                            <div class="mt-3">
                                <a href="legacy-migration-interface.php" class="btn btn-primary btn-action">
                                    <i class="fas fa-upload"></i> Migration Interface
                                </a>
                                <a href="legacy-data-converter.php" class="btn btn-info btn-action">
                                    <i class="fas fa-code"></i> Data Converter
                                </a>
                                <a href="final-guid-migration-complete.php" class="btn btn-success btn-action">
                                    <i class="fas fa-check-circle"></i> System Test
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>