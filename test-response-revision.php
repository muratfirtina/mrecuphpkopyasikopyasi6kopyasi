<?php
/**
 * Response Revision Test
 * Yanıt dosyası revize sistemini test etmek için
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/FileManager.php';

echo "<h1>🧪 Response Revision Test</h1>";

$fileManager = new FileManager($pdo);

// Test 1: Revisions tablosunda response_id alanı var mı?
echo "<h2>Test 1: Database Yapısı</h2>";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM revisions");
    $columns = $stmt->fetchAll();
    
    $hasResponseId = false;
    echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
    echo "<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'response_id') {
            $hasResponseId = true;
        }
    }
    echo "</table>";
    
    if ($hasResponseId) {
        echo "<p style='color:green;'>✅ response_id alanı var</p>";
    } else {
        echo "<p style='color:red;'>❌ response_id alanı yok</p>";
        echo "<p><a href='fix-response-revision.php'>Fix script'i çalıştır</a></p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

// Test 2: File uploads ve responses var mı?
echo "<h2>Test 2: Test Verisi</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_uploads");
    $uploadCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_responses");
    $responseCount = $stmt->fetch()['count'];
    
    echo "<p>📁 File uploads: <strong>$uploadCount</strong></p>";
    echo "<p>📨 Response files: <strong>$responseCount</strong></p>";
    
    if ($uploadCount > 0 && $responseCount > 0) {
        echo "<p style='color:green;'>✅ Test verileri mevcut</p>";
        
        // Sample response file göster
        $stmt = $pdo->query("
            SELECT fr.*, fu.original_name as upload_name, u.username
            FROM file_responses fr
            LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
            LEFT JOIN users u ON fu.user_id = u.id
            LIMIT 3
        ");
        $responses = $stmt->fetchAll();
        
        echo "<h3>Sample Response Files:</h3>";
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>Response ID</th><th>Upload</th><th>User</th><th>Response File</th></tr>";
        foreach ($responses as $response) {
            echo "<tr>";
            echo "<td>{$response['id']}</td>";
            echo "<td>{$response['upload_name']}</td>";
            echo "<td>{$response['username']}</td>";
            echo "<td>{$response['original_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color:orange;'>⚠️ Test için yeterli veri yok</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

// Test 3: Response revisions var mı?
echo "<h2>Test 3: Response Revisions</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM revisions WHERE response_id IS NOT NULL");
    $responseRevisionCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM revisions WHERE response_id IS NULL");
    $uploadRevisionCount = $stmt->fetch()['count'];
    
    echo "<p>📨 Response revisions: <strong>$responseRevisionCount</strong></p>";
    echo "<p>📁 Upload revisions: <strong>$uploadRevisionCount</strong></p>";
    
    if ($responseRevisionCount > 0) {
        echo "<p style='color:green;'>✅ Response revisions mevcut</p>";
        
        // Sample response revisions göster
        $stmt = $pdo->query("
            SELECT r.*, fr.original_name as response_name, fu.original_name as upload_name
            FROM revisions r
            LEFT JOIN file_responses fr ON r.response_id = fr.id
            LEFT JOIN file_uploads fu ON r.upload_id = fu.id
            WHERE r.response_id IS NOT NULL
            ORDER BY r.requested_at DESC
            LIMIT 5
        ");
        $revisions = $stmt->fetchAll();
        
        echo "<h3>Sample Response Revisions:</h3>";
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>Status</th><th>Response File</th><th>Upload File</th><th>Date</th></tr>";
        foreach ($revisions as $revision) {
            echo "<tr>";
            echo "<td>{$revision['status']}</td>";
            echo "<td>{$revision['response_name']}</td>";
            echo "<td>{$revision['upload_name']}</td>";
            echo "<td>{$revision['requested_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color:orange;'>⚠️ Henüz response revision yok</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

// Test 4: FileManager fonksiyonları
echo "<h2>Test 4: FileManager Fonksiyonları</h2>";
try {
    $reflection = new ReflectionClass('FileManager');
    $methods = $reflection->getMethods();
    
    $hasRequestResponseRevision = false;
    foreach ($methods as $method) {
        if ($method->getName() === 'requestResponseRevision') {
            $hasRequestResponseRevision = true;
            break;
        }
    }
    
    if ($hasRequestResponseRevision) {
        echo "<p style='color:green;'>✅ requestResponseRevision fonksiyonu mevcut</p>";
    } else {
        echo "<p style='color:red;'>❌ requestResponseRevision fonksiyonu yok</p>";
    }
    
    // Test getAllRevisions fonksiyonu
    $revisions = $fileManager->getAllRevisions(1, 5);
    echo "<p>📊 getAllRevisions test: <strong>" . count($revisions) . "</strong> revisions döndü</p>";
    
    if (count($revisions) > 0) {
        echo "<p style='color:green;'>✅ getAllRevisions çalışıyor</p>";
        
        // İlk revision'ı kontrol et
        $firstRevision = $revisions[0];
        if (isset($firstRevision['response_id'])) {
            echo "<p style='color:green;'>✅ response_id alanı getAllRevisions'da mevcut</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ response_id alanı getAllRevisions'da yok</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

// Test 5: Admin sayfaları
echo "<h2>Test 5: Admin Sayfaları</h2>";
$adminFiles = [
    'admin/revisions.php' => 'Revisions listesi',
    'admin/file-detail.php' => 'File detail sayfası',
    'admin/download-file.php' => 'Download handler'
];

foreach ($adminFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color:green;'>✅ $description ($file)</p>";
    } else {
        echo "<p style='color:red;'>❌ $description eksik ($file)</p>";
    }
}

// Test 6: Kullanıcı sayfaları
echo "<h2>Test 6: Kullanıcı Sayfaları</h2>";
$userFiles = [
    'user/files.php' => 'User files sayfası'
];

foreach ($userFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<p style='color:green;'>✅ $description ($file)</p>";
    } else {
        echo "<p style='color:red;'>❌ $description eksik ($file)</p>";
    }
}

echo "<h2>🎯 Sonuç</h2>";
echo "<p><strong>Response Revision sistemi test edildi.</strong></p>";
echo "<p>✅ Yeşil: Çalışıyor</p>";
echo "<p>⚠️ Turuncu: Uyarı</p>";
echo "<p>❌ Kırmızı: Hata</p>";

echo "<hr>";
echo "<h3>🔗 Test Linkleri</h3>";
echo "<p><a href='admin/revisions.php'>Admin Revisions</a></p>";
echo "<p><a href='user/files.php'>User Files</a></p>";
echo "<p><a href='admin/uploads.php'>Admin Uploads</a></p>";

echo "<br><br>";
echo "<p><em>Test tamamlandı: " . date('Y-m-d H:i:s') . "</em></p>";
?>
