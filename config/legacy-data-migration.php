<?php
/**
 * MR.ECU Legacy Data Migration System
 * SQL Server verilerini GUID MySQL sistemine entegre etme
 * Dosya: /config/legacy-data-migration.php
 */

require_once __DIR__ . '/../includes/functions.php';
require_once 'database.php';

// Migration sınıfı
class LegacyDataMigration {
    private $pdo;
    private $logFile;
    
    public function __construct($database) {
        $this->pdo = $database;
        $this->logFile = __DIR__ . '/../logs/migration_' . date('Y-m-d_H-i-s') . '.log';
        
        // Log klasörü yoksa oluştur
        if (!file_exists(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }
    
    // Log yazma
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        echo "<div class='log-entry'>{$logMessage}</div>";
    }
    
    // 1. Eksik tablo alanlarını ekleme
    public function addMissingColumns() {
        $this->log("=== EKSIK ALANLAR EKLENİYOR ===");
        
        try {
            // Users tablosuna eksik alanları ekle
            $this->log("Users tablosu güncelleniyor...");
            
            $userColumns = [
                "ADD COLUMN wallet DECIMAL(10,2) DEFAULT '0.00' AFTER credits",
                "ADD COLUMN is_confirm BOOLEAN DEFAULT FALSE AFTER email_verified",
                "ADD COLUMN deleted_date TIMESTAMP NULL DEFAULT NULL AFTER updated_at"
            ];
            
            foreach ($userColumns as $column) {
                try {
                    $this->pdo->exec("ALTER TABLE users $column");
                    $this->log("✓ Users: $column");
                } catch (PDOException $e) {
                    if (!strpos($e->getMessage(), 'Duplicate column name')) {
                        $this->log("⚠ Users: $column - " . $e->getMessage());
                    }
                }
            }
            
            // file_uploads tablosuna eksik alanları ekle
            $this->log("file_uploads tablosu güncelleniyor...");
            
            $fileColumns = [
                "ADD COLUMN device_type VARCHAR(100) DEFAULT NULL AFTER ecu_type",
                "ADD COLUMN kilometer VARCHAR(50) DEFAULT NULL AFTER nm_torque", 
                "ADD COLUMN plate VARCHAR(20) DEFAULT NULL AFTER kilometer",
                "ADD COLUMN type VARCHAR(100) DEFAULT NULL AFTER fuel_type",
                "ADD COLUMN motor VARCHAR(100) DEFAULT NULL AFTER engine_code",
                "ADD COLUMN code VARCHAR(50) DEFAULT NULL AFTER upload_notes",
                "ADD COLUMN price DECIMAL(10,2) DEFAULT '0.00' AFTER credits_charged",
                "ADD COLUMN updated_file_link VARCHAR(500) DEFAULT NULL AFTER filename",
                "ADD COLUMN status_text VARCHAR(100) DEFAULT NULL AFTER status"
            ];
            
            foreach ($fileColumns as $column) {
                try {
                    $this->pdo->exec("ALTER TABLE file_uploads $column");
                    $this->log("✓ file_uploads: $column");
                } catch (PDOException $e) {
                    if (!strpos($e->getMessage(), 'Duplicate column name')) {
                        $this->log("⚠ file_uploads: $column - " . $e->getMessage());
                    }
                }
            }
            
            // Status enum güncelle
            try {
                $this->pdo->exec("ALTER TABLE file_uploads MODIFY status ENUM('pending','processing','completed','rejected','ready','downloaded') DEFAULT 'pending'");
                $this->log("✓ file_uploads status enum güncellendi");
            } catch (PDOException $e) {
                $this->log("⚠ Status enum güncelleme: " . $e->getMessage());
            }
            
            return true;
            
        } catch (PDOException $e) {
            $this->log("❌ Kolon ekleme hatası: " . $e->getMessage());
            return false;
        }
    }
    
    // 2. Legacy tablolar oluşturma
    public function createLegacyTables() {
        $this->log("=== LEGACY TABLOLAR OLUŞTURULUYOR ===");
        
        try {
            // Legacy tickets tablosu
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS legacy_tickets (
                    id CHAR(36) PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    user_id CHAR(36) NOT NULL,
                    file_id CHAR(36) DEFAULT NULL,
                    status TINYINT DEFAULT 0,
                    status_text VARCHAR(100) DEFAULT NULL,
                    ticket_code VARCHAR(50) NOT NULL,
                    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (file_id) REFERENCES file_uploads(id) ON DELETE SET NULL,
                    INDEX idx_ticket_code (ticket_code),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->log("✓ legacy_tickets tablosu oluşturuldu");
            
            // Legacy ticket admin tablosu
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS legacy_ticket_admin (
                    id CHAR(36) PRIMARY KEY,
                    admin_id CHAR(36) NOT NULL,
                    ticket_id CHAR(36) NOT NULL,
                    comment TEXT NOT NULL,
                    file_link VARCHAR(500) DEFAULT NULL,
                    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (ticket_id) REFERENCES legacy_tickets(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->log("✓ legacy_ticket_admin tablosu oluşturuldu");
            
            // Legacy ticket user tablosu
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS legacy_ticket_user (
                    id CHAR(36) PRIMARY KEY,
                    ticket_id CHAR(36) NOT NULL,
                    user_id CHAR(36) NOT NULL,
                    comment TEXT NOT NULL,
                    file_link VARCHAR(500) DEFAULT NULL,
                    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (ticket_id) REFERENCES legacy_tickets(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->log("✓ legacy_ticket_user tablosu oluşturuldu");
            
            // Legacy wallet log tablosu
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS legacy_wallet_log (
                    id CHAR(36) PRIMARY KEY,
                    user_id CHAR(36) NOT NULL,
                    old_wallet DECIMAL(10,2) NOT NULL,
                    new_wallet DECIMAL(10,2) NOT NULL,
                    comment TEXT DEFAULT NULL,
                    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->log("✓ legacy_wallet_log tablosu oluşturuldu");
            
            return true;
            
        } catch (PDOException $e) {
            $this->log("❌ Legacy tablo oluşturma hatası: " . $e->getMessage());
            return false;
        }
    }
    
    // 3. Brand/Model mapping tabloları oluştur
    public function createMappingTables() {
        $this->log("=== MAPPING TABLOLARI OLUŞTURULUYOR ===");
        
        try {
            // Geçici brand mapping tablosu
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS temp_brand_mapping (
                    legacy_name VARCHAR(100) PRIMARY KEY,
                    guid_id CHAR(36) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->log("✓ temp_brand_mapping tablosu oluşturuldu");
            
            // Geçici model mapping tablosu
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS temp_model_mapping (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    legacy_brand VARCHAR(100) NOT NULL,
                    legacy_model VARCHAR(100) NOT NULL,
                    guid_brand_id CHAR(36) NOT NULL,
                    guid_model_id CHAR(36) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_brand_model (legacy_brand, legacy_model)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->log("✓ temp_model_mapping tablosu oluşturuldu");
            
            // Geçici user mapping tablosu
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS temp_user_mapping (
                    legacy_id VARCHAR(100) PRIMARY KEY,
                    guid_id CHAR(36) NOT NULL,
                    username VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $this->log("✓ temp_user_mapping tablosu oluşturuldu");
            
            return true;
            
        } catch (PDOException $e) {
            $this->log("❌ Mapping tablo oluşturma hatası: " . $e->getMessage());
            return false;
        }
    }
    
    // 4. Bulk data import fonksiyonu
    public function importUsersFromCSV($csvData) {
        $this->log("=== KULLANICI VERİLERİ İÇERİ AKTARILIYOR ===");
        
        try {
            $imported = 0;
            $skipped = 0;
            
            foreach ($csvData as $row) {
                // Gerekli alanları kontrol et
                if (empty($row['username']) || empty($row['email'])) {
                    $skipped++;
                    continue;
                }
                
                // Email var mı kontrol et
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                $stmt->execute([$row['email']]);
                if ($stmt->fetchColumn() > 0) {
                    $this->log("⚠ Email zaten var: " . $row['email']);
                    $skipped++;
                    continue;
                }
                
                // Yeni GUID oluştur
                $newUserId = generateUUID();
                
                // Role dönüştürme
                $role = (strtolower($row['user_type']) === 'admin') ? 'admin' : 'user';
                
                // Kullanıcı ekle
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (
                        id, username, email, password, first_name, last_name, 
                        phone, credits, wallet, role, status, email_verified, 
                        is_confirm, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $newUserId,
                    $row['username'],
                    $row['email'],
                    $row['password'], // Zaten hashlenmiş olmalı
                    $row['first_name'],
                    $row['last_name'],
                    $row['phone'] ?? '',
                    $row['wallet'] ?? 0,
                    $row['wallet'] ?? 0,
                    $role,
                    $row['is_confirm'] ?? 1,
                    $row['is_confirm'] ?? 1,
                    $row['created_date'] ?? date('Y-m-d H:i:s'),
                    $row['updated_date'] ?? date('Y-m-d H:i:s')
                ]);
                
                if ($result) {
                    // Mapping kaydet
                    $this->pdo->prepare("
                        INSERT INTO temp_user_mapping (legacy_id, guid_id, username, email) 
                        VALUES (?, ?, ?, ?)
                    ")->execute([
                        $row['legacy_id'], $newUserId, $row['username'], $row['email']
                    ]);
                    
                    $imported++;
                    $this->log("✓ Kullanıcı eklendi: " . $row['username']);
                } else {
                    $skipped++;
                    $this->log("❌ Kullanıcı eklenemedi: " . $row['username']);
                }
            }
            
            $this->log("=== KULLANICI İÇERİ AKTARMA TAMAMLANDI ===");
            $this->log("✓ İçeri aktarılan: $imported");
            $this->log("⚠ Atlanan: $skipped");
            
            return ['imported' => $imported, 'skipped' => $skipped];
            
        } catch (PDOException $e) {
            $this->log("❌ Kullanıcı import hatası: " . $e->getMessage());
            return false;
        }
    }
    
    // 5. Files import fonksiyonu
    public function importFilesFromCSV($csvData) {
        $this->log("=== DOSYA VERİLERİ İÇERİ AKTARILIYOR ===");
        
        try {
            $imported = 0;
            $skipped = 0;
            
            foreach ($csvData as $row) {
                // User mapping kontrol et
                $stmt = $this->pdo->prepare("SELECT guid_id FROM temp_user_mapping WHERE legacy_id = ?");
                $stmt->execute([$row['user_id']]);
                $userGuid = $stmt->fetchColumn();
                
                if (!$userGuid) {
                    $this->log("⚠ Kullanıcı bulunamadı: " . $row['user_id']);
                    $skipped++;
                    continue;
                }
                
                // Brand mapping kontrol et
                $stmt = $this->pdo->prepare("SELECT guid_id FROM temp_brand_mapping WHERE legacy_name = ?");
                $stmt->execute([$row['brand']]);
                $brandGuid = $stmt->fetchColumn();
                
                if (!$brandGuid) {
                    $this->log("⚠ Marka bulunamadı: " . $row['brand']);
                    $skipped++;
                    continue;
                }
                
                // Model mapping kontrol et
                $stmt = $this->pdo->prepare("
                    SELECT guid_model_id FROM temp_model_mapping 
                    WHERE legacy_brand = ? AND legacy_model = ?
                ");
                $stmt->execute([$row['brand'], $row['model']]);
                $modelGuid = $stmt->fetchColumn();
                
                if (!$modelGuid) {
                    $this->log("⚠ Model bulunamadı: " . $row['brand'] . " - " . $row['model']);
                    $skipped++;
                    continue;
                }
                
                // Status dönüştürme
                $statusMap = [
                    '0' => 'pending',
                    '1' => 'processing', 
                    '2' => 'completed',
                    '3' => 'rejected'
                ];
                $status = $statusMap[$row['status']] ?? 'pending';
                
                // Yeni file GUID oluştur
                $newFileId = generateUUID();
                
                // Dosya ekle
                $stmt = $this->pdo->prepare("
                    INSERT INTO file_uploads (
                        id, user_id, brand_id, model_id, year, ecu_type, engine_code, 
                        motor, device_type, gearbox_type, fuel_type, kilometer, plate, 
                        type, filename, original_name, file_size, file_type, upload_notes, 
                        code, status, status_text, admin_notes, credits_charged, price, 
                        updated_file_link, upload_date, processed_date, revision_count
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $newFileId,
                    $userGuid,
                    $brandGuid,
                    $modelGuid,
                    $row['year'] ?? 2020,
                    $row['ecu'] ?? '',
                    $row['motor'] ?? '',
                    $row['motor'] ?? '',
                    $row['device_type'] ?? '',
                    'Manual', // Varsayılan
                    'Benzin', // Varsayılan
                    $row['kilometer'] ?? '',
                    $row['plate'] ?? '',
                    $row['type'] ?? '',
                    basename($row['file_link'] ?? ''),
                    basename($row['file_link'] ?? ''),
                    0, // File size sonradan güncellenecek
                    'application/octet-stream',
                    $row['comment'] ?? '',
                    $row['code'] ?? '',
                    $status,
                    $row['status_text'] ?? '',
                    $row['admin_note'] ?? '',
                    $row['price'] ?? 0,
                    $row['price'] ?? 0,
                    $row['updated_file_link'] ?? null,
                    $row['created_date'] ?? date('Y-m-d H:i:s'),
                    ($status === 'completed') ? ($row['created_date'] ?? date('Y-m-d H:i:s')) : null,
                    0
                ]);
                
                if ($result) {
                    $imported++;
                    $this->log("✓ Dosya eklendi: " . basename($row['file_link'] ?? ''));
                } else {
                    $skipped++;
                    $this->log("❌ Dosya eklenemedi: " . basename($row['file_link'] ?? ''));
                }
            }
            
            $this->log("=== DOSYA İÇERİ AKTARMA TAMAMLANDI ===");
            $this->log("✓ İçeri aktarılan: $imported");
            $this->log("⚠ Atlanan: $skipped");
            
            return ['imported' => $imported, 'skipped' => $skipped];
            
        } catch (PDOException $e) {
            $this->log("❌ Dosya import hatası: " . $e->getMessage());
            return false;
        }
    }
    
    // 6. İstatistikler
    public function getStats() {
        $stats = [];
        
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
            $stats['total_users'] = $stmt->fetchColumn();
            
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $stats['total_admins'] = $stmt->fetchColumn();
            
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM file_uploads");
            $stats['total_files'] = $stmt->fetchColumn();
            
            $stmt = $this->pdo->query("SELECT SUM(credits) FROM users");
            $stats['total_credits'] = $stmt->fetchColumn() ?? 0;
            
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM brands");
            $stats['total_brands'] = $stmt->fetchColumn();
            
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM models");
            $stats['total_models'] = $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            $this->log("⚠ İstatistik alma hatası: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    // 7. Temizlik fonksiyonu
    public function cleanup() {
        $this->log("=== TEMİZLİK YAPILIYOR ===");
        
        try {
            $this->pdo->exec("DROP TABLE IF EXISTS temp_brand_mapping");
            $this->pdo->exec("DROP TABLE IF EXISTS temp_model_mapping");
            $this->pdo->exec("DROP TABLE IF EXISTS temp_user_mapping");
            
            $this->log("✓ Geçici tablolar temizlendi");
            return true;
            
        } catch (PDOException $e) {
            $this->log("⚠ Temizlik hatası: " . $e->getMessage());
            return false;
        }
    }
}

// Helper fonksiyonlar
if (!function_exists('generateUUID')) {
    function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
?>