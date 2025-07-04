<?php
/**
 * Mr ECU - Geliştirilmiş Güvenlik Sistemi Kurulumu
 * Güvenlik tablolarını adım adım oluşturur
 */

require_once '../config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Mr ECU - Güvenlik Sistemi Kurulumu v2</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .success { color: #28a745; margin: 10px 0; }
        .error { color: #dc3545; margin: 10px 0; }
        .warning { color: #ffc107; margin: 10px 0; }
        .info { color: #17a2b8; margin: 10px 0; }
        .step { background: #f8f9fa; padding: 15px; margin: 15px 0; border-left: 4px solid #007bff; border-radius: 4px; }
        .sql-code { background: #2d3748; color: #e2e8f0; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; overflow-x: auto; }
        .progress { background: #e9ecef; height: 20px; border-radius: 10px; margin: 20px 0; }
        .progress-bar { background: #007bff; height: 100%; border-radius: 10px; transition: width 0.3s; }
        .final-box { background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .error-box { background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .code-block { background: #f8f9fa; border: 1px solid #e9ecef; padding: 10px; border-radius: 4px; font-family: monospace; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🛡️ Mr ECU Güvenlik Sistemi Kurulumu v2</h1>";
echo "<p>Güvenlik tablolarını adım adım oluşturuyoruz...</p>";

$tables_created = 0;
$total_tables = 8;

try {
    echo "<div class='step'>";
    echo "<h3>📋 Adım 1: security_logs Tablosu</h3>";
    
    $sql = "CREATE TABLE IF NOT EXISTS security_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        event_type VARCHAR(100) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        request_uri TEXT,
        details JSON,
        user_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_event_type (event_type),
        INDEX idx_ip_address (ip_address),
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at)
    )";
    
    echo "<div class='sql-code'>" . htmlspecialchars($sql) . "</div>";
    
    $pdo->exec($sql);
    echo "<p class='success'>✅ security_logs tablosu oluşturuldu!</p>";
    $tables_created++;
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>📋 Adım 2: ip_security Tablosu</h3>";
    
    $sql = "CREATE TABLE IF NOT EXISTS ip_security (
        id INT PRIMARY KEY AUTO_INCREMENT,
        ip_address VARCHAR(45) NOT NULL UNIQUE,
        type ENUM('whitelist', 'blacklist') NOT NULL,
        reason TEXT,
        expires_at TIMESTAMP NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ip_type (ip_address, type),
        INDEX idx_expires (expires_at)
    )";
    
    echo "<div class='sql-code'>" . htmlspecialchars($sql) . "</div>";
    
    $pdo->exec($sql);
    echo "<p class='success'>✅ ip_security tablosu oluşturuldu!</p>";
    $tables_created++;
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>📋 Adım 3: failed_logins Tablosu</h3>";
    
    $sql = "CREATE TABLE IF NOT EXISTS failed_logins (
        id INT PRIMARY KEY AUTO_INCREMENT,
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
    
    echo "<div class='sql-code'>" . htmlspecialchars($sql) . "</div>";
    
    $pdo->exec($sql);
    echo "<p class='success'>✅ failed_logins tablosu oluşturuldu!</p>";
    $tables_created++;
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>📋 Adım 4: csrf_tokens Tablosu</h3>";
    
    $sql = "CREATE TABLE IF NOT EXISTS csrf_tokens (
        id INT PRIMARY KEY AUTO_INCREMENT,
        token VARCHAR(64) NOT NULL UNIQUE,
        user_id INT NULL,
        expires_at TIMESTAMP NOT NULL,
        used_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_user_expires (user_id, expires_at),
        INDEX idx_expires (expires_at)
    )";
    
    echo "<div class='sql-code'>" . htmlspecialchars($sql) . "</div>";
    
    $pdo->exec($sql);
    echo "<p class='success'>✅ csrf_tokens tablosu oluşturuldu!</p>";
    $tables_created++;
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>📋 Adım 5: rate_limits Tablosu</h3>";
    
    $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
        id INT PRIMARY KEY AUTO_INCREMENT,
        identifier VARCHAR(255) NOT NULL,
        action VARCHAR(100) NOT NULL,
        request_count INT DEFAULT 1,
        window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_request TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_identifier_action (identifier, action),
        INDEX idx_window_start (window_start),
        INDEX idx_identifier (identifier)
    )";
    
    echo "<div class='sql-code'>" . htmlspecialchars($sql) . "</div>";
    
    $pdo->exec($sql);
    echo "<p class='success'>✅ rate_limits tablosu oluşturuldu!</p>";
    $tables_created++;
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>📋 Adım 6: security_config Tablosu</h3>";
    
    $sql = "CREATE TABLE IF NOT EXISTS security_config (
        id INT PRIMARY KEY AUTO_INCREMENT,
        config_key VARCHAR(100) NOT NULL UNIQUE,
        config_value TEXT NOT NULL,
        description TEXT,
        updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_config_key (config_key)
    )";
    
    echo "<div class='sql-code'>" . htmlspecialchars($sql) . "</div>";
    
    $pdo->exec($sql);
    echo "<p class='success'>✅ security_config tablosu oluşturuldu!</p>";
    $tables_created++;
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>📋 Adım 7: file_security_scans Tablosu</h3>";
    
    $sql = "CREATE TABLE IF NOT EXISTS file_security_scans (
        id INT PRIMARY KEY AUTO_INCREMENT,
        file_path VARCHAR(500) NOT NULL,
        file_hash VARCHAR(64),
        scan_type ENUM('upload', 'periodic', 'manual') NOT NULL,
        threats_found JSON,
        status ENUM('clean', 'infected', 'suspicious', 'error') NOT NULL,
        scanned_by INT NULL,
        scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_file_path (file_path),
        INDEX idx_file_hash (file_hash),
        INDEX idx_status (status),
        INDEX idx_scan_type (scan_type)
    )";
    
    echo "<div class='sql-code'>" . htmlspecialchars($sql) . "</div>";
    
    $pdo->exec($sql);
    echo "<p class='success'>✅ file_security_scans tablosu oluşturuldu!</p>";
    $tables_created++;
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>📋 Adım 8: waf_rules Tablosu</h3>";
    
    $sql = "CREATE TABLE IF NOT EXISTS waf_rules (
        id INT PRIMARY KEY AUTO_INCREMENT,
        rule_name VARCHAR(100) NOT NULL,
        rule_type ENUM('sql_injection', 'xss', 'path_traversal', 'file_inclusion', 'command_injection', 'custom') NOT NULL,
        pattern TEXT NOT NULL,
        severity ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
        action ENUM('log', 'block', 'redirect') NOT NULL DEFAULT 'log',
        is_active BOOLEAN DEFAULT TRUE,
        description TEXT,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_rule_type (rule_type),
        INDEX idx_severity (severity),
        INDEX idx_active (is_active)
    )";
    
    echo "<div class='sql-code'>" . htmlspecialchars($sql) . "</div>";
    
    $pdo->exec($sql);
    echo "<p class='success'>✅ waf_rules tablosu oluşturuldu!</p>";
    $tables_created++;
    echo "</div>";
    
    // Progress bar
    $progress_percent = ($tables_created / $total_tables) * 100;
    echo "<div class='progress'>";
    echo "<div class='progress-bar' style='width: {$progress_percent}%'></div>";
    echo "</div>";
    echo "<p class='info'>📊 İlerleme: $tables_created/$total_tables tablo oluşturuldu</p>";
    
    // Varsayılan konfigürasyonları ekle
    echo "<div class='step'>";
    echo "<h3>⚙️ Varsayılan Konfigürasyonları Ekleniyor...</h3>";
    
    $configs = [
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
    
    foreach ($configs as $config) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO security_config (config_key, config_value, description) VALUES (?, ?, ?)");
        $stmt->execute($config);
        echo "<p class='success'>✅ Konfigürasyon eklendi: {$config[0]}</p>";
    }
    echo "</div>";
    
    // WAF kuralları ekle
    echo "<div class='step'>";
    echo "<h3>🛡️ WAF Kuralları Ekleniyor...</h3>";
    
    $waf_rules = [
        ['SQL Injection - Union Select', 'sql_injection', '/union\\s+select/i', 'high', 'block', 'UNION SELECT saldırısı tespiti'],
        ['SQL Injection - Drop Table', 'sql_injection', '/drop\\s+table/i', 'critical', 'block', 'DROP TABLE saldırısı tespiti'],
        ['XSS - Script Tag', 'xss', '/<script[^>]*>/i', 'high', 'block', 'Script tag XSS saldırısı'],
        ['XSS - JavaScript Event', 'xss', '/on\\w+\\s*=/i', 'medium', 'block', 'JavaScript event handler XSS'],
        ['Path Traversal - Directory Up', 'path_traversal', '/\\.\\.[\\/\\\\]/i', 'high', 'block', 'Directory traversal saldırısı'],
        ['File Inclusion - PHP Include', 'file_inclusion', '/(include|require)(_once)?\\s*\\(/i', 'medium', 'log', 'PHP dosya inclusion tespiti'],
        ['Command Injection - System', 'command_injection', '/(system|exec|shell_exec|passthru)\\s*\\(/i', 'critical', 'block', 'Sistem komut çalıştırma tespiti']
    ];
    
    foreach ($waf_rules as $rule) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO waf_rules (rule_name, rule_type, pattern, severity, action, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($rule);
        echo "<p class='success'>✅ WAF kuralı eklendi: {$rule[0]}</p>";
    }
    echo "</div>";
    
    // Test güvenlik olayı
    echo "<div class='step'>";
    echo "<h3>🧪 Test Güvenlik Olayı Oluşturuluyor...</h3>";
    
    $stmt = $pdo->prepare("INSERT INTO security_logs (event_type, ip_address, user_agent, request_uri, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'system_installation',
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Security Setup',
        $_SERVER['REQUEST_URI'] ?? '/security-setup',
        json_encode(['message' => 'Güvenlik sistemi başarıyla kuruldu', 'timestamp' => date('Y-m-d H:i:s'), 'version' => '1.0.0'])
    ]);
    
    echo "<p class='success'>✅ Test güvenlik olayı başarıyla kaydedildi!</p>";
    echo "</div>";
    
    // Log dizini oluştur
    echo "<div class='step'>";
    echo "<h3>📁 Log Dizini Kontrol Ediliyor...</h3>";
    
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        if (mkdir($logDir, 0755, true)) {
            echo "<p class='success'>✅ Log dizini oluşturuldu: $logDir</p>";
        } else {
            echo "<p class='error'>❌ Log dizini oluşturulamadı</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ Log dizini zaten mevcut</p>";
    }
    
    // .htaccess dosyası oluştur
    $htaccessContent = "Order Deny,Allow\nDeny from all\n";
    $htaccessFile = $logDir . '/.htaccess';
    
    if (!file_exists($htaccessFile)) {
        if (file_put_contents($htaccessFile, $htaccessContent)) {
            echo "<p class='success'>✅ Log dizini .htaccess koruması eklendi</p>";
        } else {
            echo "<p class='warning'>⚠️ .htaccess dosyası oluşturulamadı</p>";
        }
    } else {
        echo "<p class='info'>ℹ️ .htaccess koruması zaten mevcut</p>";
    }
    echo "</div>";
    
    // Final kontroller
    echo "<div class='step'>";
    echo "<h3>🔍 Final Kontroller</h3>";
    
    $required_tables = [
        'security_logs', 'ip_security', 'failed_logins', 'csrf_tokens',
        'rate_limits', 'security_config', 'file_security_scans', 'waf_rules'
    ];
    
    echo "<table>";
    echo "<tr><th>Tablo Adı</th><th>Durum</th><th>Kayıt Sayısı</th></tr>";
    
    foreach ($required_tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "<tr><td>$table</td><td style='color: #28a745;'>✅ Mevcut</td><td>$count</td></tr>";
        } catch (PDOException $e) {
            echo "<tr><td>$table</td><td style='color: #dc3545;'>❌ Bulunamadı</td><td>-</td></tr>";
        }
    }
    echo "</table>";
    echo "</div>";
    
    // Başarı mesajı
    echo "<div class='final-box'>";
    echo "<h2>🎉 Kurulum Başarıyla Tamamlandı!</h2>";
    echo "<p><strong>Mr ECU Güvenlik Sistemi v1.0 aktif!</strong></p>";
    
    echo "<h3>📊 Kurulum Özeti:</h3>";
    echo "<ul>";
    echo "<li>✅ $tables_created güvenlik tablosu oluşturuldu</li>";
    echo "<li>✅ " . count($configs) . " güvenlik konfigürasyonu eklendi</li>";
    echo "<li>✅ " . count($waf_rules) . " WAF kuralı aktifleştirildi</li>";
    echo "<li>✅ Log dizini ve koruma ayarlandı</li>";
    echo "<li>✅ Test güvenlik olayı kaydedildi</li>";
    echo "</ul>";
    
    echo "<h3>🔗 Sonraki Adımlar:</h3>";
    echo "<ol>";
    echo "<li><a href='../admin/security-dashboard.php' target='_blank'><strong>Güvenlik Dashboard'ını kontrol et</strong></a></li>";
    echo "<li><a href='../secure-login-example.php' target='_blank'><strong>Güvenli login örneğini test et</strong></a></li>";
    echo "<li><strong>Mevcut form'larınıza CSRF token entegrasyonu yapın</strong></li>";
    echo "<li><strong>Dosya yükleme sayfalarında güvenlik kontrollerini aktifleştirin</strong></li>";
    echo "</ol>";
    
    echo "<h3>📋 Güvenlik Özellikleri:</h3>";
    echo "<div class='code-block'>";
    echo "✅ SQL Injection Koruması<br>";
    echo "✅ XSS (Cross-Site Scripting) Koruması<br>";
    echo "✅ CSRF (Cross-Site Request Forgery) Koruması<br>";
    echo "✅ DOM Manipülasyon Koruması<br>";
    echo "✅ Rate Limiting & Brute Force Koruması<br>";
    echo "✅ Dosya Yükleme Güvenlik Taraması<br>";
    echo "✅ Session Hijacking Koruması<br>";
    echo "✅ Security Headers (XSS, Clickjacking, HSTS)<br>";
    echo "✅ WAF (Web Application Firewall) Kuralları<br>";
    echo "✅ Gerçek Zamanlı Güvenlik Logları<br>";
    echo "</div>";
    
    echo "<p style='margin-top: 20px;'><strong>🛡️ Artık siteniz modern siber güvenlik standartlarına tam uyumlu!</strong></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error-box'>";
    echo "<h2>❌ Kurulum Hatası</h2>";
    echo "<p><strong>Hata:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Dosya:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Satır:</strong> " . $e->getLine() . "</p>";
    
    echo "<h3>🔧 Çözüm Önerileri:</h3>";
    echo "<ul>";
    echo "<li>Veritabanı bağlantı ayarlarını kontrol edin (config/database.php)</li>";
    echo "<li>MySQL sunucusunun çalıştığından emin olun</li>";
    echo "<li>Veritabanı kullanıcısının CREATE TABLE yetkisi olduğunu kontrol edin</li>";
    echo "<li>MySQL versiyonunun 5.7+ olduğunu kontrol edin (JSON desteği için)</li>";
    echo "</ul>";
    
    echo "<h3>🐛 Debug Bilgileri:</h3>";
    echo "<div class='code-block'>";
    echo "PHP Version: " . phpversion() . "<br>";
    echo "PDO Driver: " . (extension_loaded('pdo_mysql') ? 'Yüklü' : 'Yüklü değil') . "<br>";
    echo "JSON Support: " . (function_exists('json_encode') ? 'Aktif' : 'Pasif') . "<br>";
    echo "</div>";
    echo "</div>";
}

echo "</div>";
echo "</body></html>";
?>
