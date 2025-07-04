<?php
/**
 * MR.ECU Legacy Data Migration - AJAX Handler
 * CSV dosyalarının AJAX ile işlenmesi
 */

require_once __DIR__ . '/config/legacy-data-migration.php';

header('Content-Type: application/json');

// Database bağlantısı
try {
    $database = new Database();
    $pdo = $database->getConnection();
    $migration = new LegacyDataMigration($pdo);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database bağlantı hatası: ' . $e->getMessage()]);
    exit;
}

$response = ['success' => false, 'message' => '', 'data' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'prepare_migration':
            // Migration hazırlığı
            $step1 = $migration->addMissingColumns();
            $step2 = $migration->createLegacyTables();
            $step3 = $migration->createMappingTables();
            
            if ($step1 && $step2 && $step3) {
                $response['success'] = true;
                $response['message'] = 'Migration hazırlığı tamamlandı';
            } else {
                $response['message'] = 'Migration hazırlığında hata oluştu';
            }
            break;
            
        case 'create_brand_mapping':
            // Brand mapping oluştur
            try {
                // Mevcut brandları al
                $stmt = $pdo->query("SELECT id, name FROM brands ORDER BY name");
                $brands = $stmt->fetchAll();
                
                // Mapping tablosunu temizle ve yeniden oluştur
                $pdo->exec("DELETE FROM temp_brand_mapping");
                
                $insertStmt = $pdo->prepare("INSERT INTO temp_brand_mapping (legacy_name, guid_id) VALUES (?, ?)");
                
                foreach ($brands as $brand) {
                    $insertStmt->execute([$brand['name'], $brand['id']]);
                }
                
                $response['success'] = true;
                $response['message'] = count($brands) . ' brand mapping oluşturuldu';
                $response['data'] = ['brand_count' => count($brands)];
                
            } catch (PDOException $e) {
                $response['message'] = 'Brand mapping hatası: ' . $e->getMessage();
            }
            break;
            
        case 'create_model_mapping':
            // Model mapping oluştur
            try {
                // Mevcut modelleri al
                $stmt = $pdo->query("
                    SELECT m.id as model_id, m.name as model_name, b.name as brand_name 
                    FROM models m 
                    JOIN brands b ON m.brand_id = b.id 
                    ORDER BY b.name, m.name
                ");
                $models = $stmt->fetchAll();
                
                // Mapping tablosunu temizle ve yeniden oluştur
                $pdo->exec("DELETE FROM temp_model_mapping");
                
                $insertStmt = $pdo->prepare("
                    INSERT INTO temp_model_mapping (legacy_brand, legacy_model, guid_brand_id, guid_model_id) 
                    VALUES (?, ?, ?, ?)
                ");
                
                foreach ($models as $model) {
                    // Brand GUID'ini al
                    $brandStmt = $pdo->prepare("SELECT id FROM brands WHERE name = ?");
                    $brandStmt->execute([$model['brand_name']]);
                    $brandId = $brandStmt->fetchColumn();
                    
                    $insertStmt->execute([
                        $model['brand_name'], 
                        $model['model_name'], 
                        $brandId, 
                        $model['model_id']
                    ]);
                }
                
                $response['success'] = true;
                $response['message'] = count($models) . ' model mapping oluşturuldu';
                $response['data'] = ['model_count' => count($models)];
                
            } catch (PDOException $e) {
                $response['message'] = 'Model mapping hatası: ' . $e->getMessage();
            }
            break;
            
        case 'import_users':
            // Users CSV import
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $csvData = parseCSVFile($_FILES['csv_file']['tmp_name']);
                
                if ($csvData !== false) {
                    $result = $migration->importUsersFromCSV($csvData);
                    
                    if ($result !== false) {
                        $response['success'] = true;
                        $response['message'] = "Users import tamamlandı";
                        $response['data'] = $result;
                    } else {
                        $response['message'] = 'Users import hatası';
                    }
                } else {
                    $response['message'] = 'CSV dosyası okunamadı';
                }
            } else {
                $response['message'] = 'CSV dosyası yüklenemedi';
            }
            break;
            
        case 'import_files':
            // Files CSV import
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $csvData = parseCSVFile($_FILES['csv_file']['tmp_name']);
                
                if ($csvData !== false) {
                    $result = $migration->importFilesFromCSV($csvData);
                    
                    if ($result !== false) {
                        $response['success'] = true;
                        $response['message'] = "Files import tamamlandı";
                        $response['data'] = $result;
                    } else {
                        $response['message'] = 'Files import hatası';
                    }
                } else {
                    $response['message'] = 'CSV dosyası okunamadı';
                }
            } else {
                $response['message'] = 'CSV dosyası yüklenemedi';
            }
            break;
            
        case 'import_tickets':
            // Tickets CSV import
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $csvData = parseCSVFile($_FILES['csv_file']['tmp_name']);
                
                if ($csvData !== false) {
                    $result = importTicketsFromCSV($csvData, $migration);
                    
                    if ($result !== false) {
                        $response['success'] = true;
                        $response['message'] = "Tickets import tamamlandı";
                        $response['data'] = $result;
                    } else {
                        $response['message'] = 'Tickets import hatası';
                    }
                } else {
                    $response['message'] = 'CSV dosyası okunamadı';
                }
            } else {
                $response['message'] = 'CSV dosyası yüklenemedi';
            }
            break;
            
        case 'import_wallet_log':
            // Wallet Log CSV import
            if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
                $csvData = parseCSVFile($_FILES['csv_file']['tmp_name']);
                
                if ($csvData !== false) {
                    $result = importWalletLogFromCSV($csvData, $migration);
                    
                    if ($result !== false) {
                        $response['success'] = true;
                        $response['message'] = "Wallet Log import tamamlandı";
                        $response['data'] = $result;
                    } else {
                        $response['message'] = 'Wallet Log import hatası';
                    }
                } else {
                    $response['message'] = 'CSV dosyası okunamadı';
                }
            } else {
                $response['message'] = 'CSV dosyası yüklenemedi';
            }
            break;
            
        case 'get_stats':
            // İstatistikleri al
            $stats = $migration->getStats();
            $response['success'] = true;
            $response['data'] = $stats;
            break;
            
        case 'cleanup':
            // Temizlik
            $result = $migration->cleanup();
            $response['success'] = $result;
            $response['message'] = $result ? 'Temizlik tamamlandı' : 'Temizlik hatası';
            break;
            
        default:
            $response['message'] = 'Geçersiz işlem';
    }
} else {
    $response['message'] = 'Sadece POST istekleri kabul edilir';
}

echo json_encode($response);

// CSV dosyasını parse etme fonksiyonu
function parseCSVFile($filename) {
    if (!file_exists($filename)) {
        return false;
    }
    
    $data = [];
    $header = null;
    
    if (($handle = fopen($filename, 'r')) !== false) {
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (!$header) {
                $header = $row;
            } else {
                $data[] = array_combine($header, $row);
            }
        }
        fclose($handle);
    }
    
    return $data;
}

// Tickets import helper fonksiyonu
function importTicketsFromCSV($csvData, $migration) {
    global $pdo;
    
    try {
        $imported = 0;
        $skipped = 0;
        
        foreach ($csvData as $row) {
            // User mapping kontrol et
            $stmt = $pdo->prepare("SELECT guid_id FROM temp_user_mapping WHERE legacy_id = ?");
            $stmt->execute([$row['user_id']]);
            $userGuid = $stmt->fetchColumn();
            
            if (!$userGuid) {
                $skipped++;
                continue;
            }
            
            // File mapping kontrol et (opsiyonel)
            $fileGuid = null;
            if (!empty($row['file_id'])) {
                $stmt = $pdo->prepare("SELECT id FROM file_uploads WHERE filename LIKE ? LIMIT 1");
                $stmt->execute(['%' . $row['file_id'] . '%']);
                $fileGuid = $stmt->fetchColumn();
            }
            
            // Yeni ticket GUID oluştur
            $newTicketId = generateUUID();
            
            // Legacy ticket ekle
            $stmt = $pdo->prepare("
                INSERT INTO legacy_tickets (
                    id, title, user_id, file_id, status, status_text, 
                    ticket_code, created_date, updated_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $newTicketId,
                $row['title'],
                $userGuid,
                $fileGuid,
                $row['status'],
                $row['status_text'] ?? '',
                $row['ticket_code'],
                $row['created_date'],
                $row['updated_date']
            ]);
            
            if ($result) {
                $imported++;
            } else {
                $skipped++;
            }
        }
        
        return ['imported' => $imported, 'skipped' => $skipped];
        
    } catch (PDOException $e) {
        error_log("Tickets import error: " . $e->getMessage());
        return false;
    }
}

// Wallet Log import helper fonksiyonu
function importWalletLogFromCSV($csvData, $migration) {
    global $pdo;
    
    try {
        $imported = 0;
        $skipped = 0;
        
        foreach ($csvData as $row) {
            // User mapping kontrol et
            $stmt = $pdo->prepare("SELECT guid_id FROM temp_user_mapping WHERE legacy_id = ?");
            $stmt->execute([$row['user_id']]);
            $userGuid = $stmt->fetchColumn();
            
            if (!$userGuid) {
                $skipped++;
                continue;
            }
            
            // Yeni transaction GUID oluştur
            $newTransactionId = generateUUID();
            
            // Credit transaction ekle
            $stmt = $pdo->prepare("
                INSERT INTO credit_transactions (
                    id, user_id, amount, type, description, created_at
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $newTransactionId,
                $userGuid,
                $row['amount'],
                $row['type'],
                $row['description'] ?? 'Legacy wallet transaction',
                $row['created_at']
            ]);
            
            if ($result) {
                $imported++;
            } else {
                $skipped++;
            }
        }
        
        return ['imported' => $imported, 'skipped' => $skipped];
        
    } catch (PDOException $e) {
        error_log("Wallet log import error: " . $e->getMessage());
        return false;
    }
}
?>