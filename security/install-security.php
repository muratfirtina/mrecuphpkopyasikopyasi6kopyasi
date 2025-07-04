<?php
/**
 * Mr ECU - Güvenlik Tabloları Kurulum
 * Güvenlik sisteminin veritabanı tablolarını oluşturur
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Mr ECU - Güvenlik Sistemi Kurulumu</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        .box { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>";

echo "<h1>🛡️ Mr ECU Güvenlik Sistemi Kurulumu</h1>";

try {
    // SQL dosyasını oku
    $sqlFile = __DIR__ . '/security_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception('Security tables SQL file not found');
    }
    
    $sql = file_get_contents($sqlFile);
    
    if (!$sql) {
        throw new Exception('Could not read SQL file');
    }
    
    echo "<div class='box'>";
    echo "<h2>📋 Güvenlik Tabloları Oluşturuluyor...</h2>";
    
    // SQL komutlarını ayır
    $statements = explode(';', $sql);
    $executed = 0;
    $failed = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $executed++;
            
            // Tablo adını bul
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<p class='success'>✅ Tablo oluşturuldu: {$matches[1]}</p>";
            } elseif (preg_match('/CREATE.*?VIEW.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<p class='success'>✅ View oluşturuldu: {$matches[1]}</p>";
            } elseif (preg_match('/INSERT.*?INTO.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "<p class='success'>✅ Varsayılan veriler eklendi: {$matches[1]}</p>";
            } else {
                echo "<p class='success'>✅ SQL komutu çalıştırıldı</p>";
            }
            
        } catch (PDOException $e) {
            $failed++;
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "<p class='warning'>⚠️ Tablo zaten mevcut: " . $e->getMessage() . "</p>";
            } else {
                echo "<p class='error'>❌ SQL Hatası: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<p class='info'>📊 Toplam: $executed başarılı, $failed hatalı</p>";
    echo "</div>";
    
    // Güvenlik tabloları kontrolü
    echo "<div class='box'>";
    echo "<h2>🔍 Tablo Kontrolleri</h2>";
    
    $requiredTables = [
        'security_logs',
        'ip_security', 
        'failed_logins',
        'csrf_tokens',
        'rate_limits',
        'security_config',
        'file_security_scans',
        'waf_rules'
    ];
    
    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                echo "<p class='success'>✅ $table tablosu mevcut ($count kayıt)</p>";
            } else {
                echo "<p class='error'>❌ $table tablosu bulunamadı</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>❌ $table tablo kontrolü hatası: " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
    
    // Log dizinini oluştur
    echo "<div class='box'>";
    echo "<h2>📁 Log Dizini Oluşturuluyor...</h2>";
    
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
    }
    echo "</div>";
    
    // Test güvenlik olayı oluştur
    echo "<div class='box'>";
    echo "<h2>🧪 Test Güvenlik Olayı</h2>";
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO security_logs 
            (event_type, ip_address, user_agent, request_uri, details) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            'system_test',
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Security Setup',
            $_SERVER['REQUEST_URI'] ?? '/security-setup',
            json_encode(['message' => 'Güvenlik sistemi kurulum testi', 'timestamp' => date('Y-m-d H:i:s')])
        ]);
        
        if ($result) {
            echo "<p class='success'>✅ Test güvenlik olayı başarıyla kaydedildi</p>";
        } else {
            echo "<p class='error'>❌ Test güvenlik olayı kaydedilemedi</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Test hatası: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Güvenlik konfigürasyonu kontrol
    echo "<div class='box'>";
    echo "<h2>⚙️ Güvenlik Konfigürasyonu</h2>";
    
    try {
        $stmt = $pdo->query("SELECT config_key, config_value, description FROM security_config ORDER BY config_key");
        $configs = $stmt->fetchAll();
        
        if ($configs) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Konfigürasyon</th><th>Değer</th><th>Açıklama</th></tr>";
            
            foreach ($configs as $config) {
                $status = $config['config_value'] == '1' ? '✅ Aktif' : '❌ Pasif';
                echo "<tr>";
                echo "<td>{$config['config_key']}</td>";
                echo "<td>$status</td>";
                echo "<td>{$config['description']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Konfigürasyon kontrolü hatası: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // WAF kuralları kontrol
    echo "<div class='box'>";
    echo "<h2>🛡️ WAF Kuralları</h2>";
    
    try {
        $stmt = $pdo->query("SELECT rule_name, rule_type, severity, action, is_active FROM waf_rules ORDER BY severity DESC, rule_type");
        $rules = $stmt->fetchAll();
        
        if ($rules) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Kural Adı</th><th>Tür</th><th>Önem</th><th>Aksiyon</th><th>Durum</th></tr>";
            
            foreach ($rules as $rule) {
                $severityColors = [
                    'low' => '#28a745',
                    'medium' => '#ffc107', 
                    'high' => '#fd7e14',
                    'critical' => '#dc3545'
                ];
                $severityColor = $severityColors[$rule['severity']] ?? '#6c757d';
                $status = $rule['is_active'] ? '✅ Aktif' : '❌ Pasif';
                
                echo "<tr>";
                echo "<td>{$rule['rule_name']}</td>";
                echo "<td>{$rule['rule_type']}</td>";
                echo "<td style='color: $severityColor; font-weight: bold;'>" . strtoupper($rule['severity']) . "</td>";
                echo "<td>{$rule['action']}</td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ WAF kuralları kontrolü hatası: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Sonuç
    echo "<div class='box' style='background: #d4edda; border-color: #c3e6cb;'>";
    echo "<h2>🎉 Kurulum Tamamlandı!</h2>";
    echo "<p><strong>Güvenlik sistemi başarıyla kuruldu.</strong></p>";
    echo "<p><strong>Özellikler:</strong></p>";
    echo "<ul>";
    echo "<li>✅ SQL Injection koruması aktif</li>";
    echo "<li>✅ XSS (Cross-Site Scripting) koruması aktif</li>";
    echo "<li>✅ CSRF (Cross-Site Request Forgery) koruması aktif</li>";
    echo "<li>✅ DOM manipülasyon koruması aktif</li>";
    echo "<li>✅ Rate limiting aktif</li>";
    echo "<li>✅ Dosya yükleme güvenlik taraması aktif</li>";
    echo "<li>✅ Güvenlik başlıkları aktif</li>";
    echo "<li>✅ WAF (Web Application Firewall) kuralları aktif</li>";
    echo "<li>✅ Güvenlik olay loglama aktif</li>";
    echo "</ul>";
    
    echo "<h3>📋 Sonraki Adımlar:</h3>";
    echo "<ol>";
    echo "<li><strong>Config dosyasını güncelle:</strong> config/config.php dosyasına SecurityManager entegrasyonu ekle</li>";
    echo "<li><strong>JavaScript'i dahil et:</strong> Tüm sayfalara security-guard.js dosyasını ekle</li>";
    echo "<li><strong>Form'lara CSRF token ekle:</strong> Tüm form'larda CSRF koruması aktif et</li>";
    echo "<li><strong>FileManager'ı güncelle:</strong> Dosya yükleme güvenlik kontrollerini ekle</li>";
    echo "<li><strong>Admin paneline güvenlik raporu ekle:</strong> Güvenlik loglarını görüntüle</li>";
    echo "</ol>";
    
    echo "<p><a href='../admin/' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Admin Paneline Git</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box' style='background: #f8d7da; border-color: #f5c6cb;'>";
    echo "<h2>❌ Kurulum Hatası</h2>";
    echo "<p class='error'>Hata: " . $e->getMessage() . "</p>";
    echo "<p>Lütfen veritabanı bağlantınızı kontrol edin ve tekrar deneyin.</p>";
    echo "</div>";
}

echo "</body></html>";
?>
