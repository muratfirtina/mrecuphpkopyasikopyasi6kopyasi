<?php
/**
 * Basit Database Kurulum
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Kurulum</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>📦 Database Kurulum</h1>";

try {
    echo "<h2>1. users Tablosu</h2>";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id CHAR(36) PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        phone VARCHAR(20),
        role ENUM('user', 'admin') DEFAULT 'user',
        status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
        credits DECIMAL(10,2) DEFAULT 0.00,
        email_verified BOOLEAN DEFAULT FALSE,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_email (email),
        INDEX idx_role (role),
        INDEX idx_status (status)
    )");
    echo "<div class='success'>✅ users tablosu oluşturuldu</div>";

    echo "<h2>2. file_uploads Tablosu</h2>";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS file_uploads (
        id CHAR(36) PRIMARY KEY,
        user_id CHAR(36) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        file_path VARCHAR(500),
        brand_id CHAR(36) NULL,
        model_id CHAR(36) NULL,
        status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
        notes TEXT,
        admin_notes TEXT,
        response_file VARCHAR(255) NULL,
        response_notes TEXT,
        credits_charged DECIMAL(10,2) DEFAULT 0.00,
        revision_count INT DEFAULT 0,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_upload_date (upload_date)
    )");
    echo "<div class='success'>✅ file_uploads tablosu oluşturuldu</div>";

    echo "<h2>3. revisions Tablosu</h2>";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS revisions (
        id CHAR(36) PRIMARY KEY,
        upload_id CHAR(36) NOT NULL,
        user_id CHAR(36) NOT NULL,
        admin_id CHAR(36) NULL,
        request_notes TEXT NOT NULL,
        admin_notes TEXT NULL,
        credits_charged DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('pending', 'in_progress', 'completed', 'rejected') DEFAULT 'pending',
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (upload_id) REFERENCES file_uploads(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_upload_id (upload_id),
        INDEX idx_user_id (user_id),
        INDEX idx_status (status),
        INDEX idx_requested_at (requested_at)
    )");
    echo "<div class='success'>✅ revisions tablosu oluşturuldu</div>";

    echo "<h2>4. credit_transactions Tablosu</h2>";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS credit_transactions (
        id CHAR(36) PRIMARY KEY,
        user_id CHAR(36) NOT NULL,
        admin_id CHAR(36) NULL,
        amount DECIMAL(10,2) NOT NULL,
        type ENUM('deposit', 'withdraw', 'file_charge', 'refund', 'revision_charge') NOT NULL,
        description TEXT,
        reference_id CHAR(36) NULL,
        reference_type ENUM('file_upload', 'payment', 'manual', 'revision') NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_type (type),
        INDEX idx_created_at (created_at)
    )");
    echo "<div class='success'>✅ credit_transactions tablosu oluşturuldu</div>";

    echo "<h2>5. system_logs Tablosu</h2>";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS system_logs (
        id CHAR(36) PRIMARY KEY,
        user_id CHAR(36) NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    )");
    echo "<div class='success'>✅ system_logs tablosu oluşturuldu</div>";

    echo "<h2>6. brands Tablosu</h2>";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS brands (
        id CHAR(36) PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_name (name),
        INDEX idx_is_active (is_active)
    )");
    echo "<div class='success'>✅ brands tablosu oluşturuldu</div>";
    
    // is_active alanı eksikse ekle
    try {
        $pdo->exec("ALTER TABLE brands ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
        echo "<div class='success'>✅ brands tablosuna is_active alanı eklendi</div>";
    } catch (Exception $e) {
        // Alan zaten var, devam et
    }

    echo "<h2>7. models Tablosu</h2>";
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS models (
        id CHAR(36) PRIMARY KEY,
        brand_id CHAR(36) NOT NULL,
        name VARCHAR(100) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE,
        INDEX idx_brand_id (brand_id),
        INDEX idx_name (name),
        INDEX idx_is_active (is_active)
    )");
    echo "<div class='success'>✅ models tablosu oluşturuldu</div>";
    
    // is_active alanı eksikse ekle
    try {
        $pdo->exec("ALTER TABLE models ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
        echo "<div class='success'>✅ models tablosuna is_active alanı eklendi</div>";
    } catch (Exception $e) {
        // Alan zaten var, devam et
    }

    echo "<h2>8. Örnek Veri Oluşturma</h2>";
    
    // Admin kullanıcısı kontrol
    $adminExists = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminExists == 0) {
        $adminId = generateUUID();
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (id, username, email, password, first_name, last_name, role, status, credits, created_at) 
            VALUES (?, 'admin', 'admin@mrecu.com', ?, 'Admin', 'User', 'admin', 'active', 1000.00, NOW())
        ");
        $stmt->execute([$adminId, $hashedPassword]);
        echo "<div class='success'>✅ Admin kullanıcısı oluşturuldu (admin@mrecu.com / admin123)</div>";
    }

    // Test kullanıcısı kontrol
    $testUserExists = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'testuser'")->fetchColumn();
    if ($testUserExists == 0) {
        $testUserId = generateUUID();
        $hashedPassword = password_hash('test123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (id, username, email, password, first_name, last_name, role, status, credits, created_at) 
            VALUES (?, 'testuser', 'test@mrecu.com', ?, 'Test', 'Kullanıcı', 'user', 'active', 50.00, NOW())
        ");
        $stmt->execute([$testUserId, $hashedPassword]);
        echo "<div class='success'>✅ Test kullanıcısı oluşturuldu (test@mrecu.com / test123)</div>";
    }

    // Sample brands
    $brandExists = $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();
    if ($brandExists == 0) {
        $brands = ['BMW', 'Mercedes', 'Audi', 'Volkswagen', 'Ford', 'Renault', 'Peugeot', 'Fiat'];
        foreach ($brands as $brandName) {
            $brandId = generateUUID();
            $stmt = $pdo->prepare("INSERT INTO brands (id, name) VALUES (?, ?)");
            $stmt->execute([$brandId, $brandName]);
        }
        echo "<div class='success'>✅ " . count($brands) . " marka oluşturuldu</div>";
    }

    echo "<div class='info'>🎉 Database kurulumu tamamlandı!</div>";

} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "<br><br><a href='database-test.php'>🔍 Database Testi</a> | <a href='admin/'>🏠 Admin Panel</a>";
echo "</body></html>";
?>
