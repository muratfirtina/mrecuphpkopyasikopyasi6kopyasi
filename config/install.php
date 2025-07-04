<?php
/**
 * Mr ECU - Database Installation Script
 * Veritabanı kurulum scripti
 */

require_once 'config.php';

// Veritabanı bağlantı ayarları
$host = '127.0.0.1';
$port = '8889'; // MAMP MySQL port
$username = 'root';
$password = 'root'; // Test sonucunda belirlendi
$charset = 'utf8mb4';
$dbname = 'mrecu_db';

try {
    // Önce veritabanı olmadan bağlan
    $dsn = "mysql:host=" . $host . ";port=" . $port . ";charset=" . $charset;
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Veritabanını oluştur
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$dbname} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE {$dbname}");

    // Kullanıcılar tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
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

    // Kategoriler tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        parent_id INT NULL,
        sort_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
    )");

    // Ürünler tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT,
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

    // Araç markaları tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS brands (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        logo VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Araç modelleri tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS models (
        id INT AUTO_INCREMENT PRIMARY KEY,
        brand_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        year_start INT,
        year_end INT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
    )");

    // Dosya yüklemeleri tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS file_uploads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        brand_id INT NOT NULL,
        model_id INT NOT NULL,
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
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (brand_id) REFERENCES brands(id),
        FOREIGN KEY (model_id) REFERENCES models(id)
    )");

    // Admin yanıt dosyaları tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS file_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        upload_id INT NOT NULL,
        admin_id INT NOT NULL,
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

    // Kredi işlemleri tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS credit_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        type ENUM('deposit', 'withdraw', 'file_charge', 'refund') NOT NULL,
        description TEXT,
        reference_id INT NULL,
        reference_type ENUM('file_upload', 'payment', 'manual') NULL,
        admin_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id)
    )");

    // Sistem logları tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Ayarlar tablosu
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Admin kullanıcısı oluştur
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("
    INSERT IGNORE INTO users (username, email, password, first_name, last_name, role, status, email_verified) 
    VALUES ('admin', 'admin@mrecu.com', '{$admin_password}', 'Admin', 'User', 'admin', 'active', TRUE)
    ");

    // Varsayılan kategoriler
    $pdo->exec("
    INSERT IGNORE INTO categories (name, description) VALUES 
    ('ECU Yazılımları', 'Motor kontrol ünitesi yazılımları'),
    ('TCU Yazılımları', 'Şanzıman kontrol ünitesi yazılımları'),
    ('Immobilizer', 'İmmobilizer ve anahtar programlama'),
    ('DPF/EGR', 'DPF ve EGR işlemleri'),
    ('Chip Tuning', 'Performans artırma işlemleri')
    ");

    // Varsayılan araç markaları
    $pdo->exec("
    INSERT IGNORE INTO brands (name) VALUES 
    ('Audi'), ('BMW'), ('Mercedes'), ('Volkswagen'), ('Ford'), 
    ('Opel'), ('Peugeot'), ('Renault'), ('Fiat'), ('Toyota'),
    ('Honda'), ('Hyundai'), ('Kia'), ('Nissan'), ('Mazda'),
    ('Volvo'), ('Skoda'), ('Seat'), ('Citroen'), ('Dacia'),
    ('Mitsubishi'), ('Subaru'), ('Suzuki'), ('Lexus'), ('Infiniti')
    ");

    // Varsayılan sistem ayarları
    $pdo->exec("
    INSERT IGNORE INTO settings (setting_key, setting_value, description, type) VALUES 
    ('site_maintenance', '0', 'Site bakım modu', 'boolean'),
    ('max_file_size', '52428800', 'Maksimum dosya boyutu (bytes)', 'number'),
    ('default_credits', '0', 'Yeni kullanıcı varsayılan kredi', 'number'),
    ('file_download_cost', '1', 'Dosya indirme maliyeti', 'number'),
    ('admin_email', 'admin@mrecu.com', 'Admin email adresi', 'text'),
    ('smtp_settings', '{\"host\":\"smtp.gmail.com\",\"port\":587,\"username\":\"\",\"password\":\"\"}', 'SMTP ayarları', 'json')
    ");

    echo "<!DOCTYPE html>
<html>
<head>
    <title>Mr ECU - Kurulum Tamamlandı</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .success { color: #28a745; font-size: 24px; margin-bottom: 20px; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='success'>✅ Veritabanı başarıyla kuruldu!</div>
        
        <div class='info'>
            <strong>Kurulum Detayları:</strong><br>
            - Veritabanı: mrecu_db<br>
            - Tablolar oluşturuldu<br>
            - Varsayılan veriler eklendi
        </div>
        
        <div class='info'>
            <strong>Admin Giriş Bilgileri:</strong><br>
            Kullanıcı adı: <code>admin</code><br>
            Şifre: <code>admin123</code><br>
            <small>Güvenlik için şifrenizi değiştirmeyi unutmayın!</small>
        </div>
        
        <div>
            <a href='../index.php' class='btn'>Ana Sayfaya Git</a>
            <a href='../admin/' class='btn'>Admin Paneline Git</a>
        </div>
    </div>
</body>
</html>";
    
} catch(PDOException $e) {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Kurulum Hatası</title>
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
        <div class='error'>❌ Kurulum Hatası</div>
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
