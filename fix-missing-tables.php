<?php
/**
 * Eksik Sütunları Ekle ve Hataları Düzelt
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Eksik Sütunları Ekle</title>
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

echo "<h1>🔧 Eksik Sütunları Ekle ve Hataları Düzelt</h1>";

try {
    // 1. users tablosuna last_login sütunu ekle
    echo "<h2>1. users Tablosu Güncellemeleri</h2>";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL");
        echo "<div class='success'>✅ users tablosuna last_login sütunu eklendi</div>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<div class='warning'>⚠️ last_login sütunu zaten mevcut</div>";
        } else {
            echo "<div class='error'>❌ last_login ekleme hatası: " . $e->getMessage() . "</div>";
        }
    }
    
    // 2. file_uploads tablosuna revision_count ekle
    echo "<h2>2. file_uploads Tablosu Güncellemeleri</h2>";
    try {
        $pdo->exec("ALTER TABLE file_uploads ADD COLUMN revision_count INT DEFAULT 0");
        echo "<div class='success'>✅ file_uploads tablosuna revision_count sütunu eklendi</div>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<div class='warning'>⚠️ revision_count sütunu zaten mevcut</div>";
        } else {
            echo "<div class='error'>❌ revision_count ekleme hatası: " . $e->getMessage() . "</div>";
        }
    }
    
    // 3. brands tablosunda status sütunu kontrolü
    echo "<h2>3. brands Tablosu Güncellemeleri</h2>";
    try {
        // status sütunu var mı kontrol et
        $stmt = $pdo->query("SHOW COLUMNS FROM brands LIKE 'status'");
        if ($stmt->rowCount() == 0) {
            // is_active sütunu var mı kontrol et
            $stmt = $pdo->query("SHOW COLUMNS FROM brands LIKE 'is_active'");
            if ($stmt->rowCount() > 0) {
                // is_active'den status'a dönüştür
                $pdo->exec("ALTER TABLE brands ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
                $pdo->exec("UPDATE brands SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END");
                echo "<div class='success'>✅ brands tablosuna status sütunu eklendi ve is_active'den dönüştürüldü</div>";
            } else {
                $pdo->exec("ALTER TABLE brands ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
                echo "<div class='success'>✅ brands tablosuna status sütunu eklendi</div>";
            }
        } else {
            echo "<div class='warning'>⚠️ brands.status sütunu zaten mevcut</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ brands.status ekleme hatası: " . $e->getMessage() . "</div>";
    }
    
    // 4. upload klasörlerini oluştur
    echo "<h2>4. Upload Klasörleri Kontrolü</h2>";
    $uploadDirs = [
        UPLOAD_DIR . 'user_files/',
        UPLOAD_DIR . 'response_files/',
        UPLOAD_DIR . 'revision_files/'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "<div class='success'>✅ Klasör oluşturuldu: $dir</div>";
        } else {
            echo "<div class='warning'>⚠️ Klasör zaten mevcut: $dir</div>";
        }
    }
    
    // 5. Tablolar arası uyumluluk kontrolü
    echo "<h2>5. Tablo Uyumluluk Kontrolü</h2>";
    
    $requiredTables = [
        'users' => 'Kullanıcılar',
        'file_uploads' => 'Dosya Yüklemeleri',
        'brands' => 'Markalar',
        'models' => 'Modeller',
        'revisions' => 'Revize Talepleri'
    ];
    
    foreach ($requiredTables as $table => $description) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<div class='success'>✅ $description ($table): $count kayıt</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ $description ($table): " . $e->getMessage() . "</div>";
        }
    }
    
    // 6. Test verisi oluştur (gerekirse)
    echo "<h2>6. Test Verileri Kontrolü</h2>";
    
    // Admin kullanıcısı var mı?
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        echo "<div class='warning'>⚠️ Admin kullanıcısı bulunamadı - lütfen install-guid.php'yi çalıştırın</div>";
    } else {
        echo "<div class='success'>✅ Admin kullanıcısı mevcut ($adminCount adet)</div>";
    }
    
    // Markalar var mı?
    $stmt = $pdo->query("SELECT COUNT(*) FROM brands");
    $brandCount = $stmt->fetchColumn();
    
    if ($brandCount == 0) {
        echo "<div class='warning'>⚠️ Marka bulunamadı - test markalar ekleniyor...</div>";
        
        $testBrands = [
            ['BMW', 'active'],
            ['Mercedes-Benz', 'active'],
            ['Audi', 'active'],
            ['Volkswagen', 'active'],
            ['Toyota', 'active']
        ];
        
        foreach ($testBrands as $brand) {
            $brandId = generateUUID();
            $stmt = $pdo->prepare("INSERT INTO brands (id, name, status) VALUES (?, ?, ?)");
            $stmt->execute([$brandId, $brand[0], $brand[1]]);
        }
        
        echo "<div class='success'>✅ Test markalar eklendi</div>";
    } else {
        echo "<div class='success'>✅ Markalar mevcut ($brandCount adet)</div>";
    }
    
    echo "<h2>🎉 Tüm Güncellemeler Tamamlandı!</h2>";
    echo "<div class='info'>
        <strong>Sonraki Adımlar:</strong><br>
        1. <a href='admin/debug.php'>Debug sayfasını kontrol edin</a><br>
        2. <a href='admin/uploads.php'>Admin uploads sayfasını test edin</a><br>
        3. <a href='admin/revisions.php'>Admin revisions sayfasını test edin</a><br>
        4. <a href='admin/reports.php'>Admin reports sayfasını test edin</a>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Genel hata: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
