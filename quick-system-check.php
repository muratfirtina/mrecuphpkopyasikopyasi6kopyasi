<?php
/**
 * Mr ECU - Hızlı Sistem Kontrolü
 * Dropdown sisteminin çalışır durumda olup olmadığını kontrol eder
 */

require_once 'config/config.php';
require_once 'config/database.php';

$errors = [];
$warnings = [];
$success = [];

echo "<h1>🔍 Ürünler Dropdown Sistemi - Hızlı Kontrol</h1>";
echo "<hr>";

// 1. Veritabanı Bağlantı Kontrolü
echo "<h2>1. Veritabanı Bağlantısı</h2>";
try {
    if ($pdo) {
        echo "✅ Veritabanı bağlantısı başarılı<br>";
        $success[] = "Veritabanı bağlantısı OK";
    }
} catch (Exception $e) {
    echo "❌ Veritabanı bağlantı hatası: " . $e->getMessage() . "<br>";
    $errors[] = "Veritabanı bağlantısı başarısız";
}

// 2. Gerekli Tabloların Kontrolü
echo "<h2>2. Tablo Kontrolü</h2>";
$requiredTables = [
    'categories' => 'Kategoriler tablosu',
    'products' => 'Ürünler tablosu', 
    'product_brands' => 'Ürün markaları tablosu',
    'product_images' => 'Ürün resimleri tablosu'
];

foreach ($requiredTables as $tableName => $description) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        if ($stmt->rowCount() > 0) {
            echo "✅ $description mevcut<br>";
            
            // Kayıt sayısını kontrol et
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $tableName");
            $count = $stmt->fetch()['count'];
            echo "&nbsp;&nbsp;&nbsp;&nbsp;📊 $count kayıt bulundu<br>";
            
            if ($count > 0) {
                $success[] = "$description ($count kayıt)";
            } else {
                $warnings[] = "$description boş";
            }
        } else {
            echo "❌ $description bulunamadı<br>";
            $errors[] = "$description eksik";
        }
    } catch (Exception $e) {
        echo "❌ $description kontrol hatası<br>";
        $errors[] = "$description kontrol hatası";
    }
}

// 3. Header Dropdown Test
echo "<h2>3. Header Dropdown Veri Kontrolü</h2>";
try {
    $stmt = $pdo->query("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
        WHERE c.is_active = 1 
        GROUP BY c.id 
        HAVING product_count > 0
        ORDER BY c.sort_order, c.name
        LIMIT 5
    ");
    $headerCategories = $stmt->fetchAll();
    
    if (!empty($headerCategories)) {
        echo "✅ Dropdown'da görünecek kategoriler:<br>";
        foreach ($headerCategories as $cat) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;• " . htmlspecialchars($cat['name']) . " ({$cat['product_count']} ürün)<br>";
        }
        $success[] = "Header dropdown verileri hazır (" . count($headerCategories) . " kategori)";
    } else {
        echo "⚠️ Dropdown'da görünecek kategori bulunamadı<br>";
        $warnings[] = "Header dropdown için veri yok";
    }
} catch (Exception $e) {
    echo "❌ Header dropdown veri hatası: " . $e->getMessage() . "<br>";
    $errors[] = "Header dropdown veri hatası";
}

// 4. URL Rewrite Kontrolü
echo "<h2>4. URL Rewrite (.htaccess) Kontrolü</h2>";
if (file_exists('.htaccess')) {
    $htaccess = file_get_contents('.htaccess');
    if (strpos($htaccess, 'RewriteEngine On') !== false && 
        strpos($htaccess, 'kategori/') !== false) {
        echo "✅ .htaccess dosyası ve URL rewrite kuralları mevcut<br>";
        $success[] = "URL rewrite kuralları aktif";
    } else {
        echo "⚠️ .htaccess dosyası eksik kurallar içeriyor<br>";
        $warnings[] = ".htaccess eksik kurallar";
    }
} else {
    echo "❌ .htaccess dosyası bulunamadı<br>";
    $errors[] = ".htaccess dosyası yok";
}

// 5. Gerekli Dosyaların Kontrolü
echo "<h2>5. Sayfa Dosyaları Kontrolü</h2>";
$requiredFiles = [
    'category.php' => 'Kategori sayfası',
    'category-brand-products.php' => 'Kategori-marka ürünleri sayfası',
    'products.php' => 'Ürün listesi sayfası',
    'product-detail.php' => 'Ürün detay sayfası'
];

foreach ($requiredFiles as $fileName => $description) {
    if (file_exists($fileName)) {
        echo "✅ $description mevcut<br>";
        $success[] = $description;
    } else {
        echo "❌ $description bulunamadı<br>";
        $errors[] = "$description eksik";
    }
}

// 6. Upload Klasörlerini Kontrol Et
echo "<h2>6. Upload Klasörleri</h2>";
$uploadDirs = ['uploads/categories', 'uploads/products', 'uploads/brands'];

foreach ($uploadDirs as $dir) {
    if (is_dir($dir)) {
        echo "✅ $dir klasörü mevcut<br>";
        $success[] = "$dir klasörü";
    } else {
        echo "⚠️ $dir klasörü bulunamadı<br>";
        $warnings[] = "$dir klasörü eksik";
    }
}

// 7. Sonuç Özeti
echo "<hr>";
echo "<h2>📋 Sistem Durumu Özeti</h2>";

if (empty($errors)) {
    if (empty($warnings)) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border-left: 5px solid #28a745;'>";
        echo "<h3 style='color: #155724;'>🎉 Sistem Tamamen Hazır!</h3>";
        echo "<p style='color: #155724;'>Tüm bileşenler doğru şekilde kurulmuş. Sistemi kullanmaya başlayabilirsiniz.</p>";
        echo "<p><a href='/' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Ana Sayfaya Git ve Test Et</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 5px solid #ffc107;'>";
        echo "<h3 style='color: #856404;'>⚠️ Sistem Çalışır Durumda (Bazı Uyarılar Var)</h3>";
        echo "<p style='color: #856404;'>Sistem temel olarak çalışıyor ancak bazı iyileştirmeler yapılabilir.</p>";
        echo "<ul style='color: #856404;'>";
        foreach ($warnings as $warning) {
            echo "<li>⚠️ $warning</li>";
        }
        echo "</ul>";
        echo "<p><a href='/' target='_blank' style='background: #ffc107; color: #212529; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Ana Sayfaya Git</a></p>";
        echo "</div>";
    }
} else {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 5px solid #dc3545;'>";
    echo "<h3 style='color: #721c24;'>❌ Sistem Kurulumu Tamamlanmamış</h3>";
    echo "<p style='color: #721c24;'>Aşağıdaki sorunları çözmeniz gerekiyor:</p>";
    echo "<ul style='color: #721c24;'>";
    foreach ($errors as $error) {
        echo "<li>❌ $error</li>";
    }
    echo "</ul>";
    echo "<h4 style='color: #721c24;'>Önerilen Çözümler:</h4>";
    echo "<p>";
    echo "<a href='install-categories-system.php' target='_blank' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>📦 Categories Kur</a>";
    echo "<a href='install-product-system.php' target='_blank' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🏷️ Product Brands Kur</a>";
    echo "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>📊 Detaylı Durum:</h3>";
echo "<p>✅ Başarılı: " . count($success) . " bileşen</p>";
echo "<p>⚠️ Uyarı: " . count($warnings) . " bileşen</p>";  
echo "<p>❌ Hata: " . count($errors) . " bileşen</p>";

if (count($success) > 0) {
    echo "<details><summary>✅ Başarılı Bileşenler</summary><ul>";
    foreach ($success as $item) {
        echo "<li>$item</li>";
    }
    echo "</ul></details>";
}

if (count($warnings) > 0) {
    echo "<details><summary>⚠️ Uyarılar</summary><ul>";
    foreach ($warnings as $item) {
        echo "<li>$item</li>";
    }
    echo "</ul></details>";
}

if (count($errors) > 0) {
    echo "<details><summary>❌ Hatalar</summary><ul>";
    foreach ($errors as $item) {
        echo "<li>$item</li>";
    }
    echo "</ul></details>";
}

echo "<hr>";
echo "<p><strong>Test Tarihi:</strong> " . date('d.m.Y H:i:s') . "</p>";
echo "<p><strong>Test URL:</strong> <code>" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</code></p>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #f8f9fa;
    line-height: 1.6;
}

h1, h2, h3 {
    color: #333;
}

details {
    background: #fff;
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
    border: 1px solid #ddd;
}

summary {
    cursor: pointer;
    font-weight: bold;
    padding: 5px;
}

code {
    background: #f1f3f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}
</style>
