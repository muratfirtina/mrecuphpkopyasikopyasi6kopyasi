<?php
/**
 * Mr ECU - Sistem Debug
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Sistem Debug</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔍 Sistem Debug</h1>";

// 1. Database bağlantı testi
try {
    $stmt = $pdo->query("SELECT NOW() as `current_time`, VERSION() as mysql_version");
    $info = $stmt->fetch();
    echo "<div class='success'>✅ Database bağlantısı başarılı<br>";
    echo "Zaman: {$info['current_time']}<br>";
    echo "MySQL Versiyon: {$info['mysql_version']}</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Database bağlantı hatası: " . $e->getMessage() . "</div>";
}

// 2. Gerekli tabloları kontrol et
echo "<h2>2. Tablo Kontrolü</h2>";
$requiredTables = ['users', 'file_uploads', 'revisions', 'brands', 'models', 'file_responses'];
foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "<div class='success'>✅ $table: $count kayıt</div>";
    } catch (Exception $e) {
        echo "<div class='error'>❌ $table: " . $e->getMessage() . "</div>";
    }
}

// 3. last_login sütunu kontrolü
echo "<h2>3. last_login Sütunu Kontrolü</h2>";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='success'>✅ last_login sütunu mevcut</div>";
    } else {
        echo "<div class='warning'>⚠️ last_login sütunu yok</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ last_login kontrolü hatası: " . $e->getMessage() . "</div>";
}

// 4. FileManager sınıfı kontrolü
echo "<h2>4. Sınıf Kontrolü</h2>";
if (class_exists('FileManager')) {
    echo "<div class='success'>✅ FileManager sınıfı yüklendi</div>";
    $fileManager = new FileManager($pdo);
    
    $methods = ['getUserRevisions', 'getAllRevisions', 'getUploadById'];
    foreach ($methods as $method) {
        if (method_exists($fileManager, $method)) {
            echo "<div class='success'>✅ FileManager::$method() metodu mevcut</div>";
        } else {
            echo "<div class='warning'>⚠️ FileManager::$method() metodu eksik</div>";
        }
    }
} else {
    echo "<div class='error'>❌ FileManager sınıfı bulunamadı</div>";
}

// 5. Upload klasörü kontrolü
echo "<h2>5. Upload Klasörleri</h2>";
$uploadDirs = [
    UPLOAD_DIR . 'user_files/',
    UPLOAD_DIR . 'response_files/',
    UPLOAD_DIR . 'revision_files/'
];

foreach ($uploadDirs as $dir) {
    if (is_dir($dir)) {
        $files = count(glob($dir . '*'));
        echo "<div class='success'>✅ $dir: $files dosya</div>";
    } else {
        echo "<div class='warning'>⚠️ $dir: Klasör yok</div>";
    }
}

echo "<h2>6. Hızlı Linkler</h2>";
echo "<p><a href='uploads.php'>📁 Uploads</a> | ";
echo "<a href='revisions.php'>🔄 Revisions</a> | ";
echo "<a href='reports.php'>📊 Reports</a></p>";

echo "</body></html>";
?>
