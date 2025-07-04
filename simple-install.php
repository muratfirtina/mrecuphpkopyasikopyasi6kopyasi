<?php
/**
 * Mr ECU - Basit Tablo Kurulumu
 * Simple table installation without security dependencies
 */

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <title>Mr ECU - Basit Tablo Kurulumu</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .step { background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #007bff; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🔧 Mr ECU Basit Tablo Kurulumu</h1>";

try {
    // Direkt veritabanı bağlantısı (güvenlik olmadan)
    $host = '127.0.0.1';
    $port = '8889';
    $username = 'root';
    $password = 'root';
    $dbname = 'mrecu_db_guid';
    
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='step'>";
    echo "<h3>✅ Veritabanı Bağlantısı Başarılı</h3>";
    echo "<p>$dbname veritabanına bağlanıldı.</p>";
    echo "</div>";
    
    $success_count = 0;
    
    // UUID fonksiyonu basit versiyon
    function simpleUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    // 1. user_credits tablosu
    echo "<div class='step'>";
    echo "<h3>1. user_credits Tablosu</h3>";
    
    $sql = "
    CREATE TABLE IF NOT EXISTS user_credits (
        id CHAR(36) PRIMARY KEY,
        user_id CHAR(36) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        transaction_type ENUM('deposit', 'withdraw', 'file_charge', 'refund', 'bonus', 'credit_purchase', 'file_upload', 'manual') NOT NULL,
        description TEXT,
        reference_id CHAR(36) NULL,
        reference_type ENUM('file_upload', 'payment', 'manual', 'file_response', 'revision', 'file_download') NULL,
        admin_id CHAR(36) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_transaction_type (transaction_type),
        INDEX idx_created_at (created_at)
    )";
    
    $pdo->exec($sql);
    echo "<p class='success'>✅ user_credits tablosu oluşturuldu</p>";
    $success_count++;
    echo "</div>";
    
    // 2. security_logs tablosu
    echo "<div class='step'>";
    echo "<h3>2. Güvenlik Tabloları</h3>";
    
    $security_tables = [
        'security_logs' => "
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
            INDEX idx_created_at (created_at)
        )",
        'ip_security' => "
        CREATE TABLE IF NOT EXISTS ip_security (
            id CHAR(36) PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL UNIQUE,
            type ENUM('whitelist', 'blacklist') NOT NULL,
            reason TEXT,
            expires_at TIMESTAMP NULL,
            created_by CHAR(36) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_ip_type (ip_address, type),
            INDEX idx_expires (expires_at)
        )",
        'failed_logins' => "
        CREATE TABLE IF NOT EXISTS failed_logins (
            id CHAR(36) PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            username VARCHAR(100),
            email VARCHAR(255),
            user_agent TEXT,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            blocked_until TIMESTAMP NULL,
            INDEX idx_ip_time (ip_address, attempt_time),
            INDEX idx_username (username),
            INDEX idx_email (email)
        )",
        'csrf_tokens' => "
        CREATE TABLE IF NOT EXISTS csrf_tokens (
            id CHAR(36) PRIMARY KEY,
            token VARCHAR(64) NOT NULL UNIQUE,
            user_id CHAR(36) NULL,
            expires_at TIMESTAMP NOT NULL,
            used_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        )",
        'rate_limits' => "
        CREATE TABLE IF NOT EXISTS rate_limits (
            id CHAR(36) PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            action VARCHAR(100) NOT NULL,
            request_count INT DEFAULT 1,
            window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_identifier_action (identifier, action),
            INDEX idx_window_start (window_start),
            INDEX idx_identifier (identifier)
        )",
        'security_config' => "
        CREATE TABLE IF NOT EXISTS security_config (
            id CHAR(36) PRIMARY KEY,
            config_key VARCHAR(100) NOT NULL UNIQUE,
            config_value TEXT NOT NULL,
            description TEXT,
            updated_by CHAR(36) NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_config_key (config_key)
        )",
        'file_security_scans' => "
        CREATE TABLE IF NOT EXISTS file_security_scans (
            id CHAR(36) PRIMARY KEY,
            file_path VARCHAR(500) NOT NULL,
            file_hash VARCHAR(64),
            scan_type ENUM('upload', 'periodic', 'manual') NOT NULL,
            threats_found JSON,
            status ENUM('clean', 'infected', 'suspicious', 'error') NOT NULL,
            scanned_by CHAR(36) NULL,
            scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_file_path (file_path(255)),
            INDEX idx_file_hash (file_hash),
            INDEX idx_status (status),
            INDEX idx_scan_type (scan_type)
        )",
        'waf_rules' => "
        CREATE TABLE IF NOT EXISTS waf_rules (
            id CHAR(36) PRIMARY KEY,
            rule_name VARCHAR(100) NOT NULL,
            rule_type ENUM('sql_injection', 'xss', 'path_traversal', 'file_inclusion', 'command_injection', 'custom') NOT NULL,
            pattern TEXT NOT NULL,
            severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
            action ENUM('log', 'block', 'redirect') NOT NULL DEFAULT 'log',
            is_active BOOLEAN DEFAULT TRUE,
            description TEXT,
            created_by CHAR(36) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_rule_type (rule_type),
            INDEX idx_severity (severity),
            INDEX idx_active (is_active)
        )"
    ];
    
    foreach ($security_tables as $table_name => $sql) {
        $pdo->exec($sql);
        echo "<p class='success'>✅ $table_name tablosu oluşturuldu</p>";
        $success_count++;
    }
    echo "</div>";
    
    // 3. Test verisi
    echo "<div class='step'>";
    echo "<h3>3. Test Verisi</h3>";
    
    // Test güvenlik olayı
    $pdo->exec("INSERT IGNORE INTO security_logs (id, event_type, ip_address, user_agent, request_uri, details) VALUES (
        '" . simpleUUID() . "',
        'system_installation',
        '127.0.0.1',
        'Setup Script',
        '/simple-install',
        '{\"message\": \"Basit kurulum tamamlandı\", \"version\": \"1.0\"}'
    )");
    
    echo "<p class='success'>✅ Test güvenlik olayı eklendi</p>";
    echo "</div>";
    
    // Başarı mesajı
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; border-left: 5px solid #28a745;'>";
    echo "<h2>🎉 Kurulum Başarıyla Tamamlandı!</h2>";
    echo "<p><strong>$success_count tablo başarıyla oluşturuldu.</strong></p>";
    
    echo "<h4>🔗 Sonraki Adımlar:</h4>";
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='admin/debug-database.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Database Kontrol</a>";
    echo "<a href='admin/security-dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Güvenlik Dashboard</a>";
    echo "<a href='admin/' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Admin Panel</a>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; border-left: 5px solid #dc3545;'>";
    echo "<h2>❌ Kurulum Hatası</h2>";
    echo "<p><strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Çözüm Önerileri:</strong></p>";
    echo "<ul>";
    echo "<li>MAMP/XAMPP çalıştığından emin olun</li>";
    echo "<li>MySQL portu 8889 olduğunu kontrol edin</li>";
    echo "<li>mrecu_db_guid veritabanının mevcut olduğunu kontrol edin</li>";
    echo "</ul>";
    echo "<a href='config/install-guid.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>GUID Veritabanını Kur</a>";
    echo "</div>";
}

echo "</div>";
echo "</body></html>";
?>
