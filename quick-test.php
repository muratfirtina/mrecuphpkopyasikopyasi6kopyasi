<?php
/**
 * Quick Test - Hata Düzeltmeleri Kontrolü
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Quick Test</title>
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

echo "<h1>🧪 Quick Test - Hata Düzeltmeleri</h1>";

try {
    // 1. Database bağlantı testi
    echo "<h2>1. Database Bağlantı Testi</h2>";
    $stmt = $pdo->query("SELECT NOW() as test_time");
    $time = $stmt->fetch()['test_time'];
    echo "<div class='success'>✅ Database bağlantısı başarılı: $time</div>";
    
    // 2. FileManager sınıfı yükleme testi
    echo "<h2>2. FileManager Sınıfı Testi</h2>";
    if (class_exists('FileManager')) {
        echo "<div class='success'>✅ FileManager sınıfı yüklendi</div>";
        
        $fileManager = new FileManager($pdo);
        
        // Metotların varlığını kontrol et
        $methods = ['getUserRevisions', 'getAllRevisions', 'requestRevision', 'updateRevisionStatus'];
        foreach ($methods as $method) {
            if (method_exists($fileManager, $method)) {
                echo "<div class='success'>✅ FileManager::$method() metodu mevcut</div>";
            } else {
                echo "<div class='error'>❌ FileManager::$method() metodu eksik</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ FileManager sınıfı yüklenemedi</div>";
    }
    
    // 3. Tablo kontrolü
    echo "<h2>3. Gerekli Tablolar</h2>";
    $requiredTables = ['users', 'file_uploads', 'revisions', 'brands', 'models'];
    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<div class='success'>✅ $table: $count kayıt</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ $table: " . $e->getMessage() . "</div>";
        }
    }
    
    // 4. getUserRevisions metodu test
    echo "<h2>4. getUserRevisions Metodu Testi</h2>";
    if (class_exists('FileManager') && method_exists($fileManager, 'getUserRevisions')) {
        try {
            // Test için dummy user ID (UUID format)
            $testUserId = '550e8400-e29b-41d4-a716-446655440000';
            $revisions = $fileManager->getUserRevisions($testUserId, 1, 10);
            echo "<div class='success'>✅ getUserRevisions çalıştı - " . count($revisions) . " revize bulundu</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ getUserRevisions hatası: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='error'>❌ getUserRevisions metodu bulunamadı</div>";
    }
    
    // 5. getAllRevisions metodu test
    echo "<h2>5. getAllRevisions Metodu Testi</h2>";
    if (class_exists('FileManager') && method_exists($fileManager, 'getAllRevisions')) {
        try {
            $allRevisions = $fileManager->getAllRevisions(1, 10);
            echo "<div class='success'>✅ getAllRevisions çalıştı - " . count($allRevisions) . " revize bulundu</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ getAllRevisions hatası: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='error'>❌ getAllRevisions metodu bulunamadı</div>";
    }
    
    echo "<h2>🎉 Test Tamamlandı!</h2>";
    echo "<div class='info'>
        <strong>Şimdi bu sayfaları test edebilirsiniz:</strong><br>
        1. <a href='admin/debug.php'>Debug Sayfası</a><br>
        2. <a href='admin/uploads.php'>Admin Uploads</a><br>
        3. <a href='admin/revisions.php'>Admin Revisions</a><br>
        4. <a href='admin/reports.php'>Admin Reports</a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Genel hata: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
