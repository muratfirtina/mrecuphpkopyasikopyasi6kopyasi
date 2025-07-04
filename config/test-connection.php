<?php
/**
 * Mr ECU - MAMP Ayar Kontrol Dosyası
 * Bu dosya MAMP ayarlarınızı kontrol eder
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Mr ECU - MAMP Kontrol</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        .test { margin: 15px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f1aeb5; }
        .info { background: #e7f3ff; color: #0c5460; border: 1px solid #b8daff; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 MAMP Ayar Kontrol</h1>";

// PHP Bilgileri
echo "<div class='test info'>
    <h3>PHP Bilgileri</h3>
    <strong>PHP Versiyonu:</strong> " . phpversion() . "<br>
    <strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>
    <strong>Server Port:</strong> " . $_SERVER['SERVER_PORT'] . "<br>
    <strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "
</div>";

// MySQL bağlantı testleri
$configs_to_test = [
    ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => 'root'],
    ['host' => 'localhost', 'port' => 8889, 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'port' => 8889, 'user' => 'root', 'pass' => 'root'],
    ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => 'root'],
    ['host' => '127.0.0.1', 'port' => 8889, 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'port' => 8889, 'user' => 'root', 'pass' => 'root'],
];
$successful_connection = null;

echo "<h2>🔍 MySQL Bağlantı Testleri</h2>";

foreach ($configs_to_test as $index => $config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<div class='test success'>
            <h3>✅ MySQL Bağlantı Başarılı - Config " . ($index + 1) . "</h3>
            <strong>Host:</strong> {$config['host']}<br>
            <strong>Port:</strong> {$config['port']}<br>
            <strong>Kullanıcı:</strong> {$config['user']}<br>
            <strong>Şifre:</strong> " . ($config['pass'] ? $config['pass'] : '(boş)') . "
        </div>";
        
        if (!$successful_connection) {
            $successful_connection = $config;
        }
        
        // MySQL versiyonu
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "<div class='test info'>
            <strong>MySQL Versiyonu:</strong> {$version}
        </div>";
        
        // Veritabanlarını listele
        $databases = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
        echo "<div class='test info'>
            <strong>Mevcut Veritabanları:</strong><br>" . implode(', ', $databases) . "
        </div>";
        
        break; // İlk başarılı bağlantıda dur
        
    } catch (PDOException $e) {
        if ($index < 4) { // İlk 4 denemede detay göster
            echo "<div class='test error'>
                <h3>❌ Config " . ($index + 1) . " - Bağlantı Hatası</h3>
                <strong>Host:</strong> {$config['host']}:{$config['port']}<br>
                <strong>Kullanıcı:</strong> {$config['user']}<br>
                <strong>Şifre:</strong> " . ($config['pass'] ? $config['pass'] : '(boş)') . "<br>
                <strong>Hata:</strong> " . $e->getMessage() . "
            </div>";
        }
    }
}

// Eğer hiç bağlantı başarılı olmazsa
if (!$successful_connection) {
    echo "<div class='test error'>
        <h3>❌ Hiçbir MySQL Konfigürasyonu Çalışmıyor!</h3>
        <strong>Kontrol Edilecekler:</strong><br>
        • MAMP'ın açık olduğundan emin olun<br>
        • MySQL servisinin başlatıldığından emin olun<br>
        • MAMP kontrol panelinden MySQL portunu kontrol edin<br>
        • MAMP ayarlarında MySQL şifresini kontrol edin<br>
        • Terminal'den 'sudo /Applications/MAMP/bin/mysql/bin/mysql -u root -p' deneyin
    </div>";
    
    echo "<div class='test warning'>
        <h3>🛠️ MAMP Şifre Sıfırlama</h3>
        <p>MAMP'ta MySQL şifresini sıfırlamak için:</p>
        <ol>
            <li>MAMP'ı durdurun</li>
            <li>MAMP → Preferences → Ports</li>
            <li>MySQL portunu kontrol edin (genellikle 8889)</li>
            <li>MAMP → WebStart → phpMyAdmin</li>
            <li>Orada şifre ayarlarını kontrol edin</li>
        </ol>
    </div>";
} else {
    echo "<div class='test success'>
        <h3>📋 Başarılı Bağlantı Ayarları</h3>
        <p><strong>Aşağıdaki ayarları kullanın:</strong></p>
        <pre>
Host: {$successful_connection['host']}
Port: {$successful_connection['port']}
Kullanıcı: {$successful_connection['user']}
Şifre: " . ($successful_connection['pass'] ? $successful_connection['pass'] : '(boş)') . "
        </pre>
    </div>";
}

// Dosya izinleri kontrolü
$upload_dir = '../uploads/';
if (is_dir($upload_dir)) {
    if (is_writable($upload_dir)) {
        echo "<div class='test success'>
            <h3>✅ Upload Klasörü Yazılabilir</h3>
            <strong>Klasör:</strong> {$upload_dir}
        </div>";
    } else {
        echo "<div class='test error'>
            <h3>❌ Upload Klasörü Yazılamıyor</h3>
            <strong>Klasör:</strong> {$upload_dir}<br>
            <strong>Çözüm:</strong> Klasör izinlerini 755 veya 777 yapın
        </div>";
    }
} else {
    echo "<div class='test error'>
        <h3>❌ Upload Klasörü Bulunamadı</h3>
        <strong>Beklenen:</strong> {$upload_dir}<br>
        <strong>Çözüm:</strong> uploads klasörünü oluşturun
    </div>";
}

// Server bilgileri
echo "<div class='test info'>
    <h3>🌐 Server Bilgileri</h3>
    <strong>HTTP Host:</strong> " . $_SERVER['HTTP_HOST'] . "<br>
    <strong>Request URI:</strong> " . $_SERVER['REQUEST_URI'] . "<br>
    <strong>Script Name:</strong> " . $_SERVER['SCRIPT_NAME'] . "<br>
    <strong>Query String:</strong> " . ($_SERVER['QUERY_STRING'] ?? 'yok') . "
</div>";

echo "
        <div style='margin-top: 30px;'>
            <h3>📋 Sonraki Adımlar:</h3>
            <p>Eğer MySQL bağlantısı başarılıysa:</p>";

if ($successful_connection) {
    echo "<a href='install.php' class='btn'>Kuruluma Devam Et</a>";
}

echo "
            <a href='../index.php' class='btn' style='background: #6c757d;'>Ana Sayfaya Git</a>
            <a href='javascript:location.reload()' class='btn' style='background: #17a2b8;'>Sayfayı Yenile</a>
        </div>
    </div>
</body>
</html>";
?>
