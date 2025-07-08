<?php
/**
 * Mr ECU - GUID Database Setup & Column Fix
 * GUID sistemli veritabanı oluşturma ve kolon düzeltmeleri
 */

require_once 'config.php';

// Veritabanı bağlantı ayarları
$host = '127.0.0.1';
$port = '8889'; // MAMP MySQL port
$username = 'root';
$password = 'root';
$charset = 'utf8mb4';
$dbname = 'mrecu_db';

try {
    // Veritabanı bağlantısı
    $dsn = "mysql:host=" . $host . ";port=" . $port . ";charset=" . $charset;
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Veritabanını oluştur
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$dbname} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE {$dbname}");

    // GUID yardımcı fonksiyonu
    function generateUUIDSQL() {
        return "(UPPER(CONCAT(
            LPAD(HEX(FLOOR(RAND() * 0xFFFF)), 8, '0'), '-',
            LPAD(HEX(FLOOR(RAND() * 0xFFFF)), 4, '0'), '-',
            '4', LPAD(HEX(FLOOR(RAND() * 0x0FFF)), 3, '0'), '-',
            HEX(FLOOR(RAND() * 4 + 8)), LPAD(HEX(FLOOR(RAND() * 0x0FFF)), 3, '0'), '-',
            LPAD(HEX(FLOOR(RAND() * 0xFFFFFFFF)), 8, '0'),
            LPAD(HEX(FLOOR(RAND() * 0xFFFFFFFF)), 4, '0')
        )))";
    }

    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Mr ECU - Database Setup</title>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5}.container{background:white;padding:30px;border-radius:8px;max-width:800px;margin:0 auto}.success{color:#28a745}.error{color:#dc3545}.info{color:#007bff}.step{margin:10px 0;padding:10px;background:#f8f9fa;border-left:4px solid #007bff}</style>";
    echo "</head><body><div class='container'>";
    echo "<h1>🔧 Mr ECU Database Setup</h1>";

    // 1. Users tablosu (GUID ile)
    echo "<div class='step'><strong>1. Users tablosu oluşturuluyor...</strong><br>";
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("
    CREATE TABLE users (
        id CHAR(36) PRIMARY KEY DEFAULT " . generateUUIDSQL() . ",
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        phone VARCHAR(20),
        credits DECIMAL(10,2) DEFAULT 0.00,
        role ENUM('user', 'admin') DEFAULT 'user',
        status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
        email_verified BOOLEAN DEFAULT FALSE,
        verification_token VARCHAR(255),
        reset_token VARCHAR(255),
        reset_token_expires TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<span class='success'>✅ Users tablosu oluşturuldu</span></div>";

    // 2. Brands tablosu (GUID ile)
    echo "<div class='step'><strong>2. Brands tablosu oluşturuluyor...</strong><br>";
    $pdo->exec("DROP TABLE IF EXISTS brands");
    $pdo->exec("
    CREATE TABLE brands (
        id CHAR(36) PRIMARY KEY DEFAULT " . generateUUIDSQL() . ",
        name VARCHAR(50) NOT NULL,
        logo VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<span class='success'>✅ Brands tablosu oluşturuldu</span></div>";

    // 3. Models tablosu (GUID ile)
    echo "<div class='step'><strong>3. Models tablosu oluşturuluyor...</strong><br>";
    $pdo->exec("DROP TABLE IF EXISTS models");
    $pdo->exec("
    CREATE TABLE models (
        id CHAR(36) PRIMARY KEY DEFAULT " . generateUUIDSQL() . ",
        brand_id CHAR(36) NOT NULL,
        name VARCHAR(100) NOT NULL,
        year_start INT,
        year_end INT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
    )");
    echo "<span class='success'>✅ Models tablosu oluşturuldu</span></div>";

    // 4. File Uploads tablosu (GUID ile - doğru kolon isimleri)
    echo "<div class='step'><strong>4. File Uploads tablosu oluşturuluyor...</strong><br>";
    $pdo->exec("DROP TABLE IF EXISTS file_uploads");
    $pdo->exec("
    CREATE TABLE file_uploads (
        id CHAR(36) PRIMARY KEY DEFAULT " . generateUUIDSQL() . ",
        user_id CHAR(36) NOT NULL,
        brand_id CHAR(36) NULL,
        model_id CHAR(36) NULL,
        original_filename VARCHAR(255) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        file_size BIGINT NOT NULL,
        file_path VARCHAR(500) NULL,
        year INT NULL,
        ecu_type VARCHAR(100) NULL,
        engine_code VARCHAR(50) NULL,
        gearbox_type ENUM('Manual', 'Automatic', 'CVT', 'DSG') DEFAULT 'Manual',
        fuel_type ENUM('Benzin', 'Dizel', 'LPG', 'Hybrid', 'Electric') DEFAULT 'Benzin',
        hp_power INT NULL,
        nm_torque INT NULL,
        notes TEXT NULL,
        status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
        admin_notes TEXT NULL,
        credits_charged DECIMAL(10,2) DEFAULT 0.00,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
        FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE SET NULL
    )");
    echo "<span class='success'>✅ File Uploads tablosu oluşturuldu</span></div>";

    // 5. File Responses tablosu (GUID ile)
    echo "<div class='step'><strong>5. File Responses tablosu oluşturuluyor...</strong><br>";
    $pdo->exec("DROP TABLE IF EXISTS file_responses");
    $pdo->exec("
    CREATE TABLE file_responses (
        id CHAR(36) PRIMARY KEY DEFAULT " . generateUUIDSQL() . ",
        upload_id CHAR(36) NOT NULL,
        admin_id CHAR(36) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        file_size BIGINT NOT NULL,
        file_path VARCHAR(500) NULL,
        credits_charged DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        admin_notes TEXT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        downloaded BOOLEAN DEFAULT FALSE,
        download_date TIMESTAMP NULL,
        FOREIGN KEY (upload_id) REFERENCES file_uploads(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE RESTRICT
    )");
    echo "<span class='success'>✅ File Responses tablosu oluşturuldu</span></div>";

    // 6. Revisions tablosu (GUID ile)
    echo "<div class='step'><strong>6. Revisions tablosu oluşturuluyor...</strong><br>";
    $pdo->exec("DROP TABLE IF EXISTS revisions");
    $pdo->exec("
    CREATE TABLE revisions (
        id CHAR(36) PRIMARY KEY DEFAULT " . generateUUIDSQL() . ",
        upload_id CHAR(36) NOT NULL,
        user_id CHAR(36) NOT NULL,
        admin_id CHAR(36) NULL,
        request_notes TEXT NOT NULL,
        admin_notes TEXT NULL,
        status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
        credits_charged DECIMAL(10,2) DEFAULT 0.00,
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (upload_id) REFERENCES file_uploads(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "<span class='success'>✅ Revisions tablosu oluşturuldu</span></div>";

    // 7. Credit Transactions tablosu (GUID ile)
    echo "<div class='step'><strong>7. Credit Transactions tablosu oluşturuluyor...</strong><br>";
    $pdo->exec("DROP TABLE IF EXISTS credit_transactions");
    $pdo->exec("
    CREATE TABLE credit_transactions (
        id CHAR(36) PRIMARY KEY DEFAULT " . generateUUIDSQL() . ",
        user_id CHAR(36) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        type ENUM('deposit', 'withdraw', 'file_charge', 'refund', 'revision_charge') NOT NULL,
        description TEXT NULL,
        reference_id CHAR(36) NULL,
        reference_type ENUM('file_upload', 'revision', 'payment', 'manual') NULL,
        admin_id CHAR(36) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "<span class='success'>✅ Credit Transactions tablosu oluşturuldu</span></div>";

    // 8. System Logs tablosu (GUID ile)
    echo "<div class='step'><strong>8. System Logs tablosu oluşturuluyor...</strong><br>";
    $pdo->exec("DROP TABLE IF EXISTS system_logs");
    $pdo->exec("
    CREATE TABLE system_logs (
        id CHAR(36) PRIMARY KEY DEFAULT " . generateUUIDSQL() . ",
        user_id CHAR(36) NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "<span class='success'>✅ System Logs tablosu oluşturuldu</span></div>";

    // 9. Settings tablosu (GUID ile)
    echo "<div class='step'><strong>9. Settings tablosu oluşturuluyor...</strong><br>";
    $pdo->exec("DROP TABLE IF EXISTS settings");
    $pdo->exec("
    CREATE TABLE settings (
        id CHAR(36) PRIMARY KEY DEFAULT " . generateUUIDSQL() . ",
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT NULL,
        description TEXT NULL,
        type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<span class='success'>✅ Settings tablosu oluşturuldu</span></div>";

    // Admin kullanıcısı oluştur
    echo "<div class='step'><strong>10. Admin kullanıcısı oluşturuluyor...</strong><br>";
    $adminId = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-4' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(6));
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (id, username, email, password, first_name, last_name, role, status, email_verified) 
        VALUES (?, 'admin', 'admin@mrecu.com', ?, 'Admin', 'User', 'admin', 'active', TRUE)
    ");
    $stmt->execute([$adminId, $adminPassword]);
    echo "<span class='success'>✅ Admin kullanıcısı oluşturuldu (ID: " . substr($adminId, 0, 8) . "...)</span></div>";

    // Test kullanıcısı oluştur
    echo "<div class='step'><strong>11. Test kullanıcısı oluşturuluyor...</strong><br>";
    $testUserId = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-4' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(6));
    $testPassword = password_hash('test123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (id, username, email, password, first_name, last_name, role, status, email_verified, credits) 
        VALUES (?, 'test', 'test@mrecu.com', ?, 'Test', 'User', 'user', 'active', TRUE, 100.00)
    ");
    $stmt->execute([$testUserId, $testPassword]);
    echo "<span class='success'>✅ Test kullanıcısı oluşturuldu (ID: " . substr($testUserId, 0, 8) . "...)</span></div>";

    // Varsayılan markaları ekle
    echo "<div class='step'><strong>12. Varsayılan markalar ekleniyor...</strong><br>";
    $brands = ['Audi', 'BMW', 'Mercedes', 'Volkswagen', 'Ford', 'Opel', 'Peugeot', 'Renault', 'Fiat', 'Toyota'];
    $brandIds = [];
    foreach ($brands as $brand) {
        $brandId = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-4' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(6));
        $stmt = $pdo->prepare("INSERT INTO brands (id, name) VALUES (?, ?)");
        $stmt->execute([$brandId, $brand]);
        $brandIds[$brand] = $brandId;
    }
    echo "<span class='success'>✅ " . count($brands) . " marka eklendi</span></div>";

    // Audi modelleri ekle
    echo "<div class='step'><strong>13. Sample modeller ekleniyor...</strong><br>";
    $audiModels = ['A3', 'A4', 'A6', 'Q3', 'Q5', 'TT'];
    foreach ($audiModels as $model) {
        $modelId = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-4' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(6));
        $stmt = $pdo->prepare("INSERT INTO models (id, brand_id, name, year_start, year_end) VALUES (?, ?, ?, 2010, 2024)");
        $stmt->execute([$modelId, $brandIds['Audi'], $model]);
    }
    echo "<span class='success'>✅ " . count($audiModels) . " Audi modeli eklendi</span></div>";

    // Test dosyası oluştur
    echo "<div class='step'><strong>14. Test dosyası oluşturuluyor...</strong><br>";
    $uploadId = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-4' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(6));
    $stmt = $pdo->prepare("
        INSERT INTO file_uploads (id, user_id, brand_id, original_filename, filename, file_size, status, notes) 
        VALUES (?, ?, ?, 'test_file.bin', 'test_file_stored.bin', 1024, 'completed', 'Test dosyası')
    ");
    $stmt->execute([$uploadId, $testUserId, $brandIds['Audi']]);
    echo "<span class='success'>✅ Test dosyası oluşturuldu (ID: " . substr($uploadId, 0, 8) . "...)</span></div>";

    // Upload dizinlerini oluştur
    echo "<div class='step'><strong>15. Upload dizinleri oluşturuluyor...</strong><br>";
    $uploadDirs = [
        '../uploads',
        '../uploads/user_files',
        '../uploads/response_files'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "<span class='info'>📁 $dir oluşturuldu</span><br>";
        } else {
            echo "<span class='info'>📁 $dir zaten mevcut</span><br>";
        }
    }
    echo "<span class='success'>✅ Upload dizinleri hazır</span></div>";

    echo "<div class='step' style='background:#d4edda;border-left:4px solid #28a745'>";
    echo "<h2 class='success'>🎉 Kurulum Başarıyla Tamamlandı!</h2>";
    echo "<strong>Giriş Bilgileri:</strong><br>";
    echo "<strong>Admin:</strong> admin / admin123<br>";
    echo "<strong>Test User:</strong> test / test123 (100 kredi)<br><br>";
    echo "<strong>Önemli:</strong> Güvenlik için admin şifresini değiştirin!<br>";
    echo "<a href='../index.php' style='display:inline-block;margin:10px 5px;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px'>Ana Sayfa</a>";
    echo "<a href='../login.php' style='display:inline-block;margin:10px 5px;padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px'>Giriş Yap</a>";
    echo "</div>";

    echo "</div></body></html>";
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
    echo "</div></body></html>";
}
?>
