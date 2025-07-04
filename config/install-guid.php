<?php
/**
 * Mr ECU - Database Installation Script with GUID System
 * GUID tabanlı veritabanı kurulum scripti
 */

require_once 'config.php';

// Veritabanı bağlantı ayarları
$host = '127.0.0.1';
$port = '8889'; // MAMP MySQL port
$username = 'root';
$password = 'root'; // Test sonucunda belirlendi
$charset = 'utf8mb4';
$dbname = 'mrecu_db_guid';

try {
    // Önce veritabanı olmadan bağlan
    $dsn = "mysql:host=" . $host . ";port=" . $port . ";charset=" . $charset;
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Veritabanını oluştur
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$dbname} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE {$dbname}");

    // Kullanıcılar tablosu - GUID ID ile
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id CHAR(36) PRIMARY KEY,
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

    // Kategoriler tablosu - GUID ID ile
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id CHAR(36) PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        parent_id CHAR(36) NULL,
        sort_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
    )");

    // Ürünler tablosu - GUID ID ile
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS products (
        id CHAR(36) PRIMARY KEY,
        category_id CHAR(36),
        name VARCHAR(200) NOT NULL,
        description TEXT,
        price DECIMAL(10,2),
        image VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )");

    // Araç markaları tablosu - GUID ID ile
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS brands (
        id CHAR(36) PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        logo VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Araç modelleri tablosu - GUID ID ile
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS models (
        id CHAR(36) PRIMARY KEY,
        brand_id CHAR(36) NOT NULL,
        name VARCHAR(100) NOT NULL,
        year_start INT,
        year_end INT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
    )");

    // Dosya yüklemeleri tablosu - GUID ID ile
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS file_uploads (
        id CHAR(36) PRIMARY KEY,
        user_id CHAR(36) NOT NULL,
        brand_id CHAR(36) NOT NULL,
        model_id CHAR(36) NOT NULL,
        year INT NOT NULL,
        ecu_type VARCHAR(100),
        engine_code VARCHAR(50),
        gearbox_type ENUM('Manual', 'Automatic', 'CVT', 'DSG') DEFAULT 'Manual',
        fuel_type ENUM('Benzin', 'Dizel', 'LPG', 'Hybrid', 'Electric') DEFAULT 'Benzin',
        hp_power INT,
        nm_torque INT,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(50),
        upload_notes TEXT,
        status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
        admin_notes TEXT,
        credits_charged DECIMAL(10,2) DEFAULT 0.00,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_date TIMESTAMP NULL,
        revision_count INT DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (brand_id) REFERENCES brands(id),
        FOREIGN KEY (model_id) REFERENCES models(id)
    )");

    // Admin yanıt dosyaları tablosu - GUID ID ile
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS file_responses (
        id CHAR(36) PRIMARY KEY,
        upload_id CHAR(36) NOT NULL,
        admin_id CHAR(36) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(50),
        credits_charged DECIMAL(10,2) NOT NULL,
        admin_notes TEXT,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        downloaded BOOLEAN DEFAULT FALSE,
        download_date TIMESTAMP NULL,
        FOREIGN KEY (upload_id) REFERENCES file_uploads(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id)
    )");

    // Revize tablosu - GUID ID ile
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS revisions (
        id CHAR(36) PRIMARY KEY,
        upload_id CHAR(36) NOT NULL,
        user_id CHAR(36) NOT NULL,
        admin_id CHAR(36) NULL,
        request_notes TEXT NOT NULL,
        admin_notes TEXT NULL,
        credits_charged DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (upload_id) REFERENCES file_uploads(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Revize dosyaları tablosu - GUID ID ile
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS revision_files (
        id CHAR(36) PRIMARY KEY,
        revision_id CHAR(36) NOT NULL,
        upload_id CHAR(36) NOT NULL,
        admin_id CHAR(36) NULL,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(50),
        admin_notes TEXT,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        downloaded BOOLEAN DEFAULT FALSE,
        download_date TIMESTAMP NULL,
        FOREIGN KEY (revision_id) REFERENCES revisions(id) ON DELETE CASCADE,
        FOREIGN KEY (upload_id) REFERENCES file_uploads(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Kredi işlemleri tablosu - GUID ID ile
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS credit_transactions (
        id CHAR(36) PRIMARY KEY,
        user_id CHAR(36) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        type ENUM('deposit', 'withdraw', 'file_charge', 'refund') NOT NULL,
        description TEXT,
        reference_id CHAR(36) NULL,
        reference_type ENUM('file_upload', 'payment', 'manual', 'file_response', 'revision') NULL,
        admin_id CHAR(36) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id)
    )");

    // Sistem logları tablosu - GUID ID ile
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS system_logs (
        id CHAR(36) PRIMARY KEY,
        user_id CHAR(36) NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Ayarlar tablosu - GUID ID ile
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        id CHAR(36) PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Güvenlik logları tablosu - GUID ID ile
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS security_logs (
        id CHAR(36) PRIMARY KEY,
        event_type VARCHAR(100) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        request_uri TEXT,
        details JSON,
        user_id CHAR(36) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_event_type (event_type),
        INDEX idx_ip_address (ip_address),
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Admin kullanıcısı oluştur (UUID ile)
    $adminId = generateUUID();
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("
    INSERT IGNORE INTO users (id, username, email, password, first_name, last_name, role, status, email_verified) 
    VALUES ('{$adminId}', 'admin', 'admin@mrecu.com', '{$admin_password}', 'Admin', 'User', 'admin', 'active', TRUE)
    ");

    // Varsayılan kategoriler (UUID ile)
    $categories = [
        ['id' => generateUUID(), 'name' => 'ECU Yazılımları', 'description' => 'Motor kontrol ünitesi yazılımları'],
        ['id' => generateUUID(), 'name' => 'TCU Yazılımları', 'description' => 'Şanzıman kontrol ünitesi yazılımları'],
        ['id' => generateUUID(), 'name' => 'Immobilizer', 'description' => 'İmmobilizer ve anahtar programlama'],
        ['id' => generateUUID(), 'name' => 'DPF/EGR', 'description' => 'DPF ve EGR işlemleri'],
        ['id' => generateUUID(), 'name' => 'Chip Tuning', 'description' => 'Performans artırma işlemleri']
    ];
    
    foreach ($categories as $cat) {
        $pdo->exec("
        INSERT IGNORE INTO categories (id, name, description) 
        VALUES ('{$cat['id']}', '{$cat['name']}', '{$cat['description']}')
        ");
    }

    // Varsayılan araç markaları (UUID ile)
    $brands = ['Audi', 'BMW', 'Mercedes', 'Volkswagen', 'Ford', 'Opel', 'Peugeot', 'Renault', 'Fiat', 'Toyota',
               'Honda', 'Hyundai', 'Kia', 'Nissan', 'Mazda', 'Volvo', 'Skoda', 'Seat', 'Citroen', 'Dacia',
               'Mitsubishi', 'Subaru', 'Suzuki', 'Lexus', 'Infiniti'];
    
    foreach ($brands as $brand) {
        $brandId = generateUUID();
        $pdo->exec("INSERT IGNORE INTO brands (id, name) VALUES ('{$brandId}', '{$brand}')");
    }

    // Varsayılan sistem ayarları (UUID ile)
    $settings = [
        ['id' => generateUUID(), 'key' => 'site_maintenance', 'value' => '0', 'description' => 'Site bakım modu', 'type' => 'boolean'],
        ['id' => generateUUID(), 'key' => 'max_file_size', 'value' => '52428800', 'description' => 'Maksimum dosya boyutu (bytes)', 'type' => 'number'],
        ['id' => generateUUID(), 'key' => 'default_credits', 'value' => '0', 'description' => 'Yeni kullanıcı varsayılan kredi', 'type' => 'number'],
        ['id' => generateUUID(), 'key' => 'file_download_cost', 'value' => '1', 'description' => 'Dosya indirme maliyeti', 'type' => 'number'],
        ['id' => generateUUID(), 'key' => 'admin_email', 'value' => 'admin@mrecu.com', 'description' => 'Admin email adresi', 'type' => 'text'],
        ['id' => generateUUID(), 'key' => 'smtp_settings', 'value' => '{"host":"smtp.gmail.com","port":587,"username":"","password":""}', 'description' => 'SMTP ayarları', 'type' => 'json']
    ];
    
    foreach ($settings as $setting) {
        $pdo->exec("
        INSERT IGNORE INTO settings (id, setting_key, setting_value, description, type) 
        VALUES ('{$setting['id']}', '{$setting['key']}', '{$setting['value']}', '{$setting['description']}', '{$setting['type']}')
        ");
    }

    echo "<!DOCTYPE html>
<html>
<head>
    <title>Mr ECU - GUID Kurulum Tamamlandı</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .success { color: #28a745; font-size: 24px; margin-bottom: 20px; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='success'>✅ GUID Veritabanı başarıyla kuruldu!</div>
        
        <div class='warning'>
            <strong>🔄 ÖNEMLİ: Sistem GUID Moduna Geçirildi</strong><br>
            Bu kurulum yeni GUID tabanlı veritabanı oluşturdu. Eski INT ID sistemi ile uyumlu değildir.<br>
            <strong>Veritabanı Adı:</strong> <code>{$dbname}</code>
        </div>
        
        <div class='info'>
            <strong>Kurulum Detayları:</strong><br>
            - Veritabanı: {$dbname}<br>
            - GUID ID sistemi aktif<br>
            - Tüm tablolar CHAR(36) UUID formatında<br>
            - Varsayılan veriler eklendi<br>
            - Admin ID: <code>{$adminId}</code>
        </div>
        
        <div class='info'>
            <strong>Admin Giriş Bilgileri:</strong><br>
            Kullanıcı adı: <code>admin</code><br>
            Şifre: <code>admin123</code><br>
            <small>Güvenlik için şifrenizi değiştirmeyi unutmayın!</small>
        </div>
        
        <div class='info'>
            <strong>Sonraki Adımlar:</strong><br>
            1. database.php dosyasındaki veritabanı adını '{$dbname}' olarak güncelleyin<br>
            2. User.php ve FileManager.php sınıflarını GUID sistemine uygun olarak güncelleyin<br>
            3. Tüm frontend kodlarında ID validasyonlarını GUID formatına uygun hale getirin
        </div>
        
        <div>
            <a href='../index.php' class='btn'>Ana Sayfaya Git</a>
            <a href='../admin/' class='btn'>Admin Paneline Git</a>
            <a href='install-guid-users.php' class='btn'>User Sınıfını Güncelle</a>
        </div>
    </div>
</body>
</html>";
    
} catch(PDOException $e) {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>GUID Kurulum Hatası</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .error { color: #dc3545; font-size: 24px; margin-bottom: 20px; }
        .details { background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='error'>❌ GUID Kurulum Hatası</div>
        <div class='details'>
            <strong>Hata:</strong> " . $e->getMessage() . "<br><br>
            <strong>Olası Çözümler:</strong><br>
            - MAMP/XAMPP'ın çalıştığından emin olun<br>
            - MySQL servisinin aktif olduğunu kontrol edin<br>
            - Database.php dosyasındaki bağlantı bilgilerini kontrol edin<br>
            - MySQL root şifresini kontrol edin
        </div>
        <a href='javascript:history.back()' class='btn'>Geri Dön</a>
    </div>
</body>
</html>";
}
?>
