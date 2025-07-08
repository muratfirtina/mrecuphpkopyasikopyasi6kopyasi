<?php
/**
 * file_uploads Tablosunu Düzelt
 * Eksik kolonları ekler ve yapıyı günceller
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Upload Tablosu Düzeltme</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔧 file_uploads Tablosu Düzeltme</h1>";

try {
    // Önce mevcut yapıyı kontrol et
    echo "<h2>1. Mevcut Tablo Yapısı</h2>";
    $columns = $pdo->query("DESCRIBE file_uploads")->fetchAll();
    $existingColumns = array_column($columns, 'Field');
    
    echo "<div class='info'>Mevcut kolonlar: " . implode(', ', $existingColumns) . "</div>";
    
    // Eksik kolonları tespit et
    $requiredColumns = [
        'brand_id' => 'VARCHAR(36) NULL',
        'model_id' => 'VARCHAR(36) NULL', 
        'year' => 'INT NULL',
        'ecu_type' => 'VARCHAR(100) NULL',
        'engine_code' => 'VARCHAR(50) NULL',
        'gearbox_type' => 'VARCHAR(20) NULL DEFAULT "Manual"',
        'fuel_type' => 'VARCHAR(20) NULL DEFAULT "Benzin"',
        'hp_power' => 'INT NULL',
        'nm_torque' => 'INT NULL'
    ];
    
    $missingColumns = [];
    foreach ($requiredColumns as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            $missingColumns[$column] = $definition;
        }
    }
    
    if (empty($missingColumns)) {
        echo "<div class='success'>✅ Tüm gerekli kolonlar mevcut!</div>";
    } else {
        echo "<h2>2. Eksik Kolonları Ekleme</h2>";
        echo "<div class='info'>Eklenecek kolonlar: " . implode(', ', array_keys($missingColumns)) . "</div>";
        
        foreach ($missingColumns as $column => $definition) {
            try {
                $sql = "ALTER TABLE file_uploads ADD COLUMN $column $definition";
                $pdo->exec($sql);
                echo "<div class='success'>✅ $column kolonu eklendi</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ $column kolonu eklenemedi: " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // file_responses tablosunu kontrol et
    echo "<h2>3. file_responses Tablosu Kontrol</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('file_responses', $tables)) {
        echo "<div class='info'>file_responses tablosu bulunamadı, oluşturuluyor...</div>";
        
        $sql = "CREATE TABLE file_responses (
            id VARCHAR(36) PRIMARY KEY,
            upload_id VARCHAR(36) NOT NULL,
            admin_id VARCHAR(36) NULL,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_size INT NOT NULL DEFAULT 0,
            credits_charged DECIMAL(10,2) DEFAULT 0.00,
            admin_notes TEXT NULL,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (upload_id) REFERENCES file_uploads(id) ON DELETE CASCADE,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_upload_id (upload_id),
            INDEX idx_admin_id (admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        try {
            $pdo->exec($sql);
            echo "<div class='success'>✅ file_responses tablosu oluşturuldu</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ file_responses tablosu oluşturulamadı: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='success'>✅ file_responses tablosu zaten mevcut</div>";
        
        // Tablo yapısını kontrol et
        $columns = $pdo->query("DESCRIBE file_responses")->fetchAll();
        $existingColumns = array_column($columns, 'Field');
        
        $requiredResponseColumns = [
            'id', 'upload_id', 'admin_id', 'filename', 'original_name', 
            'file_size', 'credits_charged', 'admin_notes', 'upload_date'
        ];
        
        $missingResponseColumns = array_diff($requiredResponseColumns, $existingColumns);
        
        if (!empty($missingResponseColumns)) {
            echo "<div class='info'>Eksik kolonlar bulundu, ekleniyor: " . implode(', ', $missingResponseColumns) . "</div>";
            
            foreach ($missingResponseColumns as $column) {
                $definition = '';
                switch($column) {
                    case 'admin_notes':
                        $definition = 'TEXT NULL';
                        break;
                    case 'credits_charged':
                        $definition = 'DECIMAL(10,2) DEFAULT 0.00';
                        break;
                    case 'file_size':
                        $definition = 'INT NOT NULL DEFAULT 0';
                        break;
                    default:
                        continue 2;
                }
                
                try {
                    $sql = "ALTER TABLE file_responses ADD COLUMN $column $definition";
                    $pdo->exec($sql);
                    echo "<div class='success'>✅ $column kolonu eklendi</div>";
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ $column kolonu eklenemedi: " . $e->getMessage() . "</div>";
                }
            }
        } else {
            echo "<div class='success'>✅ file_responses tablosunda tüm gerekli kolonlar mevcut</div>";
        }
        
        // Kayıt sayısını göster
        $responseCount = $pdo->query("SELECT COUNT(*) FROM file_responses")->fetchColumn();
        echo "<div class='info'>📄 file_responses tablosunda $responseCount yanıt dosyası bulunuyor</div>";
        
        if ($responseCount == 0) {
            echo "<div class='info'>Test yanıt dosyası oluşturuluyor...</div>";
            
            // Test için tamamlanmış dosya var mı kontrol et
            $completedUpload = $pdo->query("SELECT id, user_id FROM file_uploads WHERE status = 'completed' LIMIT 1")->fetch();
            if ($completedUpload) {
                $testResponseId = generateUUID();
                $stmt = $pdo->prepare("
                    INSERT INTO file_responses (id, upload_id, admin_id, filename, original_name, file_size, admin_notes, upload_date) 
                    VALUES (?, ?, NULL, 'test_response.bin', 'Test Yanıt Dosyası.bin', 2048, 'Test admin yanıt dosyası', NOW())
                ");
                if ($stmt->execute([$testResponseId, $completedUpload['id']])) {
                    echo "<div class='success'>✅ Test yanıt dosyası oluşturuldu</div>";
                }
            } else {
                echo "<div class='info'>Tamamlanmış dosya bulunamadı, test yanıt dosyası oluşturulamadı</div>";
            }
        }
    }
    
    echo "<h2>4. Foreign Key Kontrolü</h2>";
    try {
        // brands tablosu var mı kontrol et
        $tables = $pdo->query("SHOW TABLES LIKE 'brands'")->fetchAll();
        if (empty($tables)) {
            echo "<div class='info'>brands tablosu bulunamadı, oluşturuluyor...</div>";
            
            $sql = "CREATE TABLE brands (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $pdo->exec($sql);
            
            // Test verileri ekle
            $brands = [
                ['id' => generateUUID(), 'name' => 'BMW'],
                ['id' => generateUUID(), 'name' => 'Mercedes-Benz'],
                ['id' => generateUUID(), 'name' => 'Audi'],
                ['id' => generateUUID(), 'name' => 'Volkswagen'],
                ['id' => generateUUID(), 'name' => 'Toyota'],
                ['id' => generateUUID(), 'name' => 'Honda'],
                ['id' => generateUUID(), 'name' => 'Ford'],
                ['id' => generateUUID(), 'name' => 'Renault'],
                ['id' => generateUUID(), 'name' => 'Peugeot'],
                ['id' => generateUUID(), 'name' => 'Fiat']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO brands (id, name) VALUES (?, ?)");
            foreach ($brands as $brand) {
                $stmt->execute([$brand['id'], $brand['name']]);
            }
            
            echo "<div class='success'>✅ brands tablosu oluşturuldu ve test verileri eklendi</div>";
        }
        
        // models tablosu var mı kontrol et
        $tables = $pdo->query("SHOW TABLES LIKE 'models'")->fetchAll();
        if (empty($tables)) {
            echo "<div class='info'>models tablosu bulunamadı, oluşturuluyor...</div>";
            
            $sql = "CREATE TABLE models (
                id VARCHAR(36) PRIMARY KEY,
                brand_id VARCHAR(36) NOT NULL,
                name VARCHAR(100) NOT NULL,
                year_start INT DEFAULT 2000,
                year_end INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
                INDEX idx_brand_id (brand_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $pdo->exec($sql);
            
            // Test modelleri ekle
            $bmwId = $pdo->query("SELECT id FROM brands WHERE name = 'BMW' LIMIT 1")->fetchColumn();
            $mercedesId = $pdo->query("SELECT id FROM brands WHERE name = 'Mercedes-Benz' LIMIT 1")->fetchColumn();
            
            if ($bmwId && $mercedesId) {
                $models = [
                    ['id' => generateUUID(), 'brand_id' => $bmwId, 'name' => '3 Series', 'year_start' => 2010],
                    ['id' => generateUUID(), 'brand_id' => $bmwId, 'name' => '5 Series', 'year_start' => 2010],
                    ['id' => generateUUID(), 'brand_id' => $bmwId, 'name' => 'X3', 'year_start' => 2015],
                    ['id' => generateUUID(), 'brand_id' => $mercedesId, 'name' => 'C-Class', 'year_start' => 2010],
                    ['id' => generateUUID(), 'brand_id' => $mercedesId, 'name' => 'E-Class', 'year_start' => 2010],
                    ['id' => generateUUID(), 'brand_id' => $mercedesId, 'name' => 'GLA', 'year_start' => 2015]
                ];
                
                $stmt = $pdo->prepare("INSERT INTO models (id, brand_id, name, year_start) VALUES (?, ?, ?, ?)");
                foreach ($models as $model) {
                    $stmt->execute([$model['id'], $model['brand_id'], $model['name'], $model['year_start']]);
                }
                
                echo "<div class='success'>✅ models tablosu oluşturuldu ve test modelleri eklendi</div>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Foreign key hatası: " . $e->getMessage() . "</div>";
    }
    
    // user_files klasörünü oluştur
    echo "<h2>5. Dosya Klasörleri</h2>";
    $userFilesDir = UPLOAD_PATH . 'user_files/';
    if (!is_dir($userFilesDir)) {
        if (mkdir($userFilesDir, 0755, true)) {
            echo "<div class='success'>✅ user_files klasörü oluşturuldu</div>";
        } else {
            echo "<div class='error'>❌ user_files klasörü oluşturulamadı</div>";
        }
    } else {
        echo "<div class='success'>✅ user_files klasörü zaten mevcut</div>";
    }
    
    $responseFilesDir = UPLOAD_PATH . 'response_files/';
    if (!is_dir($responseFilesDir)) {
        if (mkdir($responseFilesDir, 0755, true)) {
            echo "<div class='success'>✅ response_files klasörü oluşturuldu</div>";
        } else {
            echo "<div class='error'>❌ response_files klasörü oluşturulamadı</div>";
        }
    } else {
        echo "<div class='success'>✅ response_files klasörü zaten mevcut</div>";
    }
    
    echo "<h2>6. Final Kontrol</h2>";
    $finalColumns = $pdo->query("DESCRIBE file_uploads")->fetchAll();
    $finalColumnNames = array_column($finalColumns, 'Field');
    
    echo "<div class='success'>✅ Final tablo yapısı:</div>";
    echo "<ul>";
    foreach ($finalColumns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} " . 
             ($column['Null'] === 'YES' ? '(NULL)' : '(NOT NULL)') . "</li>";
    }
    echo "</ul>";
    
    // Test upload yapabilir miyiz kontrol et
    echo "<h2>7. Test Upload Kontrolü</h2>";
    $requiredForUpload = ['id', 'user_id', 'original_name', 'filename', 'file_size', 'brand_id', 'model_id', 'year', 'status', 'upload_date'];
    $missing = array_diff($requiredForUpload, $finalColumnNames);
    
    if (empty($missing)) {
        echo "<div class='success'>✅ Upload işlemi için tüm gerekli kolonlar mevcut!</div>";
    } else {
        echo "<div class='error'>❌ Upload için eksik kolonlar: " . implode(', ', $missing) . "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Genel Hata: " . $e->getMessage() . "</div>";
}

echo "<br><br><a href='user/upload.php'>📁 Upload sayfasını test et</a> | <a href='admin/'>🏠 Admin paneline git</a>";
echo "</body></html>";
?>
