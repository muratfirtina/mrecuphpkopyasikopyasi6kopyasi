<?php
/**
 * Database Test ve Debug Dosyası
 * files.php'deki hataları tespit etmek için
 */

require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn()) {
    die('Lütfen giriş yapın');
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Database Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { border: 1px solid #ddd; margin: 10px 0; padding: 15px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>";

echo "<h1>🔍 Database Debug Test</h1>";

// 1. Bağlantı testi
echo "<div class='section'>";
echo "<h2>1. Database Bağlantı Testi</h2>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<div class='success'>✅ Database bağlantısı başarılı</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Database bağlantı hatası: " . $e->getMessage() . "</div>";
}
echo "</div>";

// 2. Tablo varlık kontrolü
echo "<div class='section'>";
echo "<h2>2. Tablo Varlık Kontrolü</h2>";

$requiredTables = ['users', 'file_uploads', 'file_responses', 'revisions', 'brands', 'models'];

foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch() !== false;
        
        if ($exists) {
            echo "<div class='success'>✅ $table tablosu mevcut</div>";
            
            // Tablo yapısını göster
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            echo "<details><summary>Tablo yapısı ($table)</summary>";
            echo "<table>";
            echo "<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th></tr>";
            foreach ($columns as $col) {
                echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
            }
            echo "</table></details>";
        } else {
            echo "<div class='error'>❌ $table tablosu mevcut değil</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ $table kontrolü hatası: " . $e->getMessage() . "</div>";
    }
}
echo "</div>";

// 3. file_uploads tablosu veri kontrolü
echo "<div class='section'>";
echo "<h2>3. file_uploads Veri Kontrolü</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_uploads");
    $count = $stmt->fetch()['count'];
    echo "<div class='success'>✅ file_uploads tablosunda $count kayıt var</div>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT id, user_id, original_filename, status, upload_date FROM file_uploads ORDER BY upload_date DESC LIMIT 3");
        $files = $stmt->fetchAll();
        
        echo "<h4>Son yüklenen dosyalar:</h4>";
        echo "<table>";
        echo "<tr><th>ID</th><th>User ID</th><th>Dosya Adı</th><th>Durum</th><th>Tarih</th></tr>";
        foreach ($files as $file) {
            echo "<tr>";
            echo "<td>" . substr($file['id'], 0, 8) . "...</td>";
            echo "<td>" . substr($file['user_id'], 0, 8) . "...</td>";
            echo "<td>{$file['original_filename']}</td>";
            echo "<td>{$file['status']}</td>";
            echo "<td>{$file['upload_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ file_uploads veri kontrolü hatası: " . $e->getMessage() . "</div>";
}
echo "</div>";

// 4. FileManager sınıfı testi
echo "<div class='section'>";
echo "<h2>4. FileManager Sınıf Testi</h2>";
try {
    $fileManager = new FileManager($pdo);
    echo "<div class='success'>✅ FileManager sınıfı başarıyla oluşturuldu</div>";
    
    // getUserUploads metodunu test et
    $userId = $_SESSION['user_id'];
    echo "<div>Test edilen User ID: " . substr($userId, 0, 8) . "...</div>";
    
    $uploads = $fileManager->getUserUploads($userId, 1, 5);
    echo "<div class='success'>✅ getUserUploads metodu çalıştı. Sonuç sayısı: " . count($uploads) . "</div>";
    
    // getUploadById metodunu test et (eğer dosya varsa)
    if (!empty($uploads)) {
        $firstFileId = $uploads[0]['id'];
        echo "<div>Test edilen File ID: " . substr($firstFileId, 0, 8) . "...</div>";
        
        $upload = $fileManager->getUploadById($firstFileId);
        if ($upload) {
            echo "<div class='success'>✅ getUploadById metodu çalıştı</div>";
        } else {
            echo "<div class='error'>❌ getUploadById metodu null döndü</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ FileManager test hatası: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Hata detayı: " . $e->getTraceAsString() . "</div>";
}
echo "</div>";

// 5. AJAX endpoint testi
echo "<div class='section'>";
echo "<h2>5. AJAX Endpoint Testi</h2>";
if (!empty($uploads)) {
    $testFileId = $uploads[0]['id'];
    echo "<div>Test URL: files.php?get_file_details=1&file_id=$testFileId</div>";
    
    // Simulate AJAX request
    $_GET['get_file_details'] = '1';
    $_GET['file_id'] = $testFileId;
    
    if (!isValidUUID($testFileId)) {
        echo "<div class='error'>❌ GUID format hatası</div>";
    } else {
        echo "<div class='success'>✅ GUID format doğru</div>";
        
        $upload = $fileManager->getUploadById($testFileId);
        if (!$upload) {
            echo "<div class='error'>❌ getUploadById null döndü</div>";
        } else {
            if ($upload['user_id'] !== $userId) {
                echo "<div class='error'>❌ Dosya kullanıcıya ait değil</div>";
            } else {
                echo "<div class='success'>✅ AJAX endpoint başarılı olur</div>";
            }
        }
    }
} else {
    echo "<div class='warning'>⚠️ Test edilecek dosya yok</div>";
}
echo "</div>";

// 6. Download metodu test
echo "<div class='section'>";
echo "<h2>6. Download Metodu Testi</h2>";
try {
    if (!empty($uploads)) {
        $testFileId = $uploads[0]['id'];
        $result = $fileManager->downloadFile($testFileId, $userId, 'response');
        
        if ($result['success']) {
            echo "<div class='success'>✅ downloadFile metodu başarılı</div>";
            echo "<div>Dosya yolu: {$result['file_path']}</div>";
            echo "<div>Orijinal adı: {$result['original_name']}</div>";
            
            // Dosya gerçekten var mı?
            if (file_exists($result['file_path'])) {
                echo "<div class='success'>✅ Fiziksel dosya mevcut</div>";
            } else {
                echo "<div class='error'>❌ Fiziksel dosya bulunamadı: {$result['file_path']}</div>";
            }
        } else {
            echo "<div class='error'>❌ downloadFile hatası: {$result['message']}</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ Test edilecek dosya yok</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Download test hatası: " . $e->getMessage() . "</div>";
}
echo "</div>";

// 7. Dosya yolu kontrolü
echo "<div class='section'>";
echo "<h2>7. Dosya Yolu Kontrolü</h2>";
echo "<div>UPLOAD_PATH: " . UPLOAD_PATH . "</div>";
echo "<div>UPLOAD_DIR: " . UPLOAD_DIR . "</div>";

$uploadDirs = [
    UPLOAD_PATH,
    UPLOAD_PATH . 'user_files/',
    UPLOAD_PATH . 'response_files/'
];

foreach ($uploadDirs as $dir) {
    if (is_dir($dir)) {
        echo "<div class='success'>✅ Dizin mevcut: $dir</div>";
        
        // Dizin yazılabilir mi?
        if (is_writable($dir)) {
            echo "<div class='success'>✅ Dizin yazılabilir: $dir</div>";
        } else {
            echo "<div class='error'>❌ Dizin yazılabilir değil: $dir</div>";
        }
        
        // Dizinde dosya var mı?
        $files = scandir($dir);
        $fileCount = count($files) - 2; // . ve .. hariç
        echo "<div>Dosya sayısı: $fileCount</div>";
        
    } else {
        echo "<div class='error'>❌ Dizin mevcut değil: $dir</div>";
    }
}
echo "</div>";

echo "</body></html>";
?>
