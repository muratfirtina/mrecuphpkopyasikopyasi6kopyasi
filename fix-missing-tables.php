<?php
/**
 * Mr ECU - Eksik Tabloları Oluştur ve Güvenlik Sistemini Entegre Et
 * Missing Tables & Security Integration
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Mr ECU - Eksik Tabloları Oluştur</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
    <style>
        .step-box {
            background: rgba(255,255,255,0.9);
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .header-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <div class='header-box'>
        <h1><i class='fas fa-tools me-3'></i>Mr ECU Eksik Tabloları Oluştur</h1>
        <p class='lead mb-0'>User Credits ve Güvenlik tablolarını oluşturuyoruz...</p>
    </div>";

$success_count = 0;
$error_count = 0;

try {
    // 1. user_credits tablosunu oluştur
    echo "<div class='step-box'>";
    echo "<h4><i class='fas fa-coins me-2'></i>1. User Credits Tablosu</h4>";
    
    $user_credits_sql = "
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
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_transaction_type (transaction_type),
        INDEX idx_created_at (created_at)
    )";
    
    $pdo->exec($user_credits_sql);
    echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ user_credits tablosu oluşturuldu</p>";
    $success_count++;
    
    // Mevcut users tablosundaki credits'i user_credits'e aktar
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_credits");
    $existing_credits = $stmt->fetch()['count'];
    
    if ($existing_credits == 0) {
        $stmt = $pdo->query("SELECT id, credits FROM users WHERE credits > 0");
        $users_with_credits = $stmt->fetchAll();
        
        if (!empty($users_with_credits)) {
            $insert_stmt = $pdo->prepare("
                INSERT INTO user_credits (id, user_id, amount, transaction_type, description) 
                VALUES (?, ?, ?, 'manual', 'Mevcut kredi transferi')
            ");
            
            foreach ($users_with_credits as $user) {
                $insert_stmt->execute([generateUUID(), $user['id'], $user['credits']]);
            }
            
            echo "<p class='success'><i class='fas fa-sync me-2'></i>✅ " . count($users_with_credits) . " kullanıcının kredisi transfer edildi</p>";
        }
    }
    echo "</div>";
    
    // 2. Güvenlik tablolarını oluştur
    echo "<div class='step-box'>";
    echo "<h4><i class='fas fa-shield-alt me-2'></i>2. Güvenlik Tabloları</h4>";
    
    // Security logs
    $security_logs_sql = "
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
    )";
    
    $pdo->exec($security_logs_sql);
    echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ security_logs tablosu oluşturuldu</p>";
    $success_count++;
    
    // IP Security
    $ip_security_sql = "
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
        INDEX idx_expires (expires_at),
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    $pdo->exec($ip_security_sql);
    echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ ip_security tablosu oluşturuldu</p>";
    $success_count++;
    
    // Failed logins
    $failed_logins_sql = "
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
    )";
    
    $pdo->exec($failed_logins_sql);
    echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ failed_logins tablosu oluşturuldu</p>";
    $success_count++;
    
    // CSRF tokens
    $csrf_tokens_sql = "
    CREATE TABLE IF NOT EXISTS csrf_tokens (
        id CHAR(36) PRIMARY KEY,
        token VARCHAR(64) NOT NULL UNIQUE,
        user_id CHAR(36) NULL,
        expires_at TIMESTAMP NOT NULL,
        used_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_user_expires (user_id, expires_at),
        INDEX idx_expires (expires_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($csrf_tokens_sql);
    echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ csrf_tokens tablosu oluşturuldu</p>";
    $success_count++;
    
    // Rate limits
    $rate_limits_sql = "
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
    )";
    
    $pdo->exec($rate_limits_sql);
    echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ rate_limits tablosu oluşturuldu</p>";
    $success_count++;
    
    // Security config
    $security_config_sql = "
    CREATE TABLE IF NOT EXISTS security_config (
        id CHAR(36) PRIMARY KEY,
        config_key VARCHAR(100) NOT NULL UNIQUE,
        config_value TEXT NOT NULL,
        description TEXT,
        updated_by CHAR(36) NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_config_key (config_key),
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    $pdo->exec($security_config_sql);
    echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ security_config tablosu oluşturuldu</p>";
    $success_count++;
    
    // File security scans
    $file_security_scans_sql = "
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
        INDEX idx_scan_type (scan_type),
        FOREIGN KEY (scanned_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    $pdo->exec($file_security_scans_sql);
    echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ file_security_scans tablosu oluşturuldu</p>";
    $success_count++;
    
    // WAF rules
    $waf_rules_sql = "
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
        INDEX idx_active (is_active),
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    $pdo->exec($waf_rules_sql);
    echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ waf_rules tablosu oluşturuldu</p>";
    $success_count++;
    
    echo "</div>";
    
    // 3. Varsayılan güvenlik konfigürasyonları
    echo "<div class='step-box'>";
    echo "<h4><i class='fas fa-cogs me-2'></i>3. Varsayılan Güvenlik Konfigürasyonları</h4>";
    
    $security_configs = [
        ['max_login_attempts', '5', 'Maksimum giriş deneme sayısı'],
        ['login_block_duration', '900', 'Giriş bloklama süresi (saniye)'],
        ['session_timeout', '3600', 'Session zaman aşımı (saniye)'],
        ['file_upload_scan', '1', 'Dosya yükleme güvenlik taraması (1=aktif, 0=pasif)'],
        ['xss_protection', '1', 'XSS koruması (1=aktif, 0=pasif)'],
        ['sql_injection_protection', '1', 'SQL Injection koruması (1=aktif, 0=pasif)'],
        ['csrf_protection', '1', 'CSRF koruması (1=aktif, 0=pasif)'],
        ['rate_limiting', '1', 'Rate limiting (1=aktif, 0=pasif)'],
        ['security_headers', '1', 'Güvenlik başlıkları (1=aktif, 0=pasif)'],
        ['ip_whitelist_enabled', '0', 'IP whitelist kontrolü (1=aktif, 0=pasif)']
    ];
    
    $config_stmt = $pdo->prepare("INSERT IGNORE INTO security_config (id, config_key, config_value, description) VALUES (?, ?, ?, ?)");
    
    foreach ($security_configs as $config) {
        $config_stmt->execute([generateUUID(), $config[0], $config[1], $config[2]]);
    }
    
    echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ " . count($security_configs) . " güvenlik konfigürasyonu eklendi</p>";
    $success_count++;
    
    echo "</div>";
    
    // 4. Varsayılan WAF kuralları
    echo "<div class='step-box'>";
    echo "<h4><i class='fas fa-shield me-2'></i>4. WAF (Web Application Firewall) Kuralları</h4>";
    
    $waf_rules = [
        ['SQL Injection - Union Select', 'sql_injection', '/union\\s+select/i', 'high', 'block', 'UNION SELECT saldırısı tespiti'],
        ['SQL Injection - Drop Table', 'sql_injection', '/drop\\s+table/i', 'critical', 'block', 'DROP TABLE saldırısı tespiti'],
        ['XSS - Script Tag', 'xss', '/<script[^>]*>/i', 'high', 'block', 'Script tag XSS saldırısı'],
        ['XSS - JavaScript Event', 'xss', '/on\\w+\\s*=/i', 'medium', 'block', 'JavaScript event handler XSS'],
        ['Path Traversal - Directory Up', 'path_traversal', '/\\.\\.[\\/\\\\]/i', 'high', 'block', 'Directory traversal saldırısı'],
        ['File Inclusion - PHP Include', 'file_inclusion', '/(include|require)(_once)?\\s*\\(/i', 'medium', 'log', 'PHP dosya inclusion tespiti'],
        ['Command Injection - System', 'command_injection', '/(system|exec|shell_exec|passthru)\\s*\\(/i', 'critical', 'block', 'Sistem komut çalıştırma tespiti']
    ];
    
    $rule_stmt = $pdo->prepare("INSERT IGNORE INTO waf_rules (id, rule_name, rule_type, pattern, severity, action, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($waf_rules as $rule) {
        $rule_stmt->execute([generateUUID(), $rule[0], $rule[1], $rule[2], $rule[3], $rule[4], $rule[5]]);
    }
    
    echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ " . count($waf_rules) . " WAF kuralı eklendi</p>";
    $success_count++;
    
    echo "</div>";
    
    // 5. Test güvenlik olayı
    echo "<div class='step-box'>";
    echo "<h4><i class='fas fa-test me-2'></i>5. Test Güvenlik Olayı</h4>";
    
    $test_event_stmt = $pdo->prepare("INSERT INTO security_logs (id, event_type, ip_address, user_agent, request_uri, details) VALUES (?, ?, ?, ?, ?, ?)");
    $test_event_stmt->execute([
        generateUUID(),
        'system_installation',
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Security Setup',
        $_SERVER['REQUEST_URI'] ?? '/security-setup',
        json_encode(['message' => 'Güvenlik sistemi başarıyla kuruldu', 'timestamp' => date('Y-m-d H:i:s'), 'version' => '2.0.0'])
    ]);
    
    echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ Test güvenlik olayı kaydedildi</p>";
    $success_count++;
    
    echo "</div>";
    
    // Final durum
    echo "<div class='alert alert-success mt-4'>";
    echo "<h3><i class='fas fa-trophy me-2'></i>🎉 Kurulum Başarıyla Tamamlandı!</h3>";
    echo "<p class='mb-3'><strong>Mr ECU Güvenlik Sistemi v2.0 aktif!</strong></p>";
    
    echo "<h5>📊 Kurulum Özeti:</h5>";
    echo "<ul class='mb-3'>";
    echo "<li>✅ $success_count tablo/konfigürasyon başarıyla oluşturuldu</li>";
    echo "<li>✅ " . count($security_configs) . " güvenlik konfigürasyonu eklendi</li>";
    echo "<li>✅ " . count($waf_rules) . " WAF kuralı aktifleştirildi</li>";
    echo "<li>✅ User credits sistemi kuruldu</li>";
    echo "<li>✅ Test güvenlik olayı kaydedildi</li>";
    echo "</ul>";
    
    echo "<h5>🔗 Sonraki Adımlar:</h5>";
    echo "<div class='row mt-3'>";
    echo "<div class='col-md-3'>";
    echo "<a href='admin/debug-database.php' class='btn btn-primary w-100 mb-2'>";
    echo "<i class='fas fa-database me-2'></i>Database Kontrol";
    echo "</a>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<a href='admin/security-dashboard.php' class='btn btn-success w-100 mb-2'>";
    echo "<i class='fas fa-shield-alt me-2'></i>Güvenlik Dashboard";
    echo "</a>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<a href='final-guid-migration-complete.php' class='btn btn-warning w-100 mb-2'>";
    echo "<i class='fas fa-clipboard-check me-2'></i>GUID Test";
    echo "</a>";
    echo "</div>";
    echo "<div class='col-md-3'>";
    echo "<a href='admin/' class='btn btn-info w-100 mb-2'>";
    echo "<i class='fas fa-cog me-2'></i>Admin Panel";
    echo "</a>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h3><i class='fas fa-times-circle me-2'></i>❌ Kurulum Hatası</h3>";
    echo "<p><strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Dosya:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Satır:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
    $error_count++;
}

echo "<div class='text-center mt-4'>";
echo "<small class='text-muted'>Kurulum tamamlandı: " . date('Y-m-d H:i:s') . "</small>";
echo "</div>";

echo "</div>";
echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>";
echo "</body></html>";
?>
