<?php
/**
 * Mr ECU - Image Path Debug
 * Görsel sorunlarını tespit etmek için
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>🔍 Image Path Debug Analizi</h1>";
echo "<hr>";

try {
    // 1. Product Brands kontrolü
    echo "<h2>1. Product Brands - Logo Kontrolü</h2>";
    $stmt = $pdo->query("SELECT id, name, slug, logo FROM product_brands WHERE is_active = 1 LIMIT 5");
    $brands = $stmt->fetchAll();
    
    if (!empty($brands)) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Marka</th><th>Slug</th><th>Logo Path</th><th>Logo Test</th><th>Dosya Var mı?</th></tr>";
        
        foreach ($brands as $brand) {
            echo "<tr>";
            echo "<td>{$brand['id']}</td>";
            echo "<td>" . htmlspecialchars($brand['name']) . "</td>";
            echo "<td>" . htmlspecialchars($brand['slug']) . "</td>";
            echo "<td><code>" . htmlspecialchars($brand['logo'] ?? 'NULL') . "</code></td>";
            
            if ($brand['logo']) {
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/' . $brand['logo'];
                $fileExists = file_exists($fullPath) ? '✅ VAR' : '❌ YOK';
                
                echo "<td><img src='/mrecuphpkopyasikopyasi6kopyasi/{$brand['logo']}' style='max-width: 100px; max-height: 50px;' onerror='this.style.display=\"none\"; this.nextSibling.style.display=\"inline\";'><span style='display:none; color:red;'>❌ YÜKLENEMEDI</span></td>";
                echo "<td>$fileExists<br><small>$fullPath</small></td>";
            } else {
                echo "<td>Logo yok</td>";
                echo "<td>-</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>⚠️ Hiç aktif marka bulunamadı!</div>";
    }

    // 2. Products ve Product Images kontrolü  
    echo "<h2>2. Products - Ürün Resim Kontrolü</h2>";
    $stmt = $pdo->query("
        SELECT p.id, p.name, p.slug, 
               (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as primary_image,
               (SELECT COUNT(*) FROM product_images WHERE product_id = p.id) as image_count
        FROM products p 
        WHERE p.is_active = 1 
        LIMIT 5
    ");
    $products = $stmt->fetchAll();
    
    if (!empty($products)) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Ürün</th><th>Slug</th><th>Ana Resim Path</th><th>Resim Test</th><th>Dosya Var mı?</th><th>Toplam Resim</th></tr>";
        
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>" . htmlspecialchars(substr($product['name'], 0, 30)) . "...</td>";
            echo "<td>" . htmlspecialchars($product['slug']) . "</td>";
            echo "<td><code>" . htmlspecialchars($product['primary_image'] ?? 'NULL') . "</code></td>";
            
            if ($product['primary_image']) {
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/' . $product['primary_image'];
                $fileExists = file_exists($fullPath) ? '✅ VAR' : '❌ YOK';
                
                echo "<td><img src='/mrecuphpkopyasikopyasi6kopyasi/{$product['primary_image']}' style='max-width: 100px; max-height: 50px;' onerror='this.style.display=\"none\"; this.nextSibling.style.display=\"inline\";'><span style='display:none; color:red;'>❌ YÜKLENEMEDI</span></td>";
                echo "<td>$fileExists<br><small>$fullPath</small></td>";
            } else {
                echo "<td>Resim yok</td>";
                echo "<td>-</td>";
            }
            echo "<td>{$product['image_count']} resim</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>⚠️ Hiç aktif ürün bulunamadı!</div>";
    }

    // 3. Product Images tablosu detay kontrolü
    echo "<h2>3. Product Images Tablosu Detay</h2>";
    $stmt = $pdo->query("SELECT * FROM product_images ORDER BY product_id, sort_order LIMIT 10");
    $productImages = $stmt->fetchAll();
    
    if (!empty($productImages)) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; margin: 20px 0; font-size: 12px;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Product ID</th><th>Image Path</th><th>Alt Text</th><th>Ana Resim</th><th>Sıra</th><th>Test</th></tr>";
        
        foreach ($productImages as $img) {
            echo "<tr>";
            echo "<td>{$img['id']}</td>";
            echo "<td>{$img['product_id']}</td>";
            echo "<td><code>" . htmlspecialchars($img['image_path']) . "</code></td>";
            echo "<td>" . htmlspecialchars($img['alt_text'] ?? '') . "</td>";
            echo "<td>" . ($img['is_primary'] ? '⭐ ANA' : '') . "</td>";
            echo "<td>{$img['sort_order']}</td>";
            
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/' . $img['image_path'];
            $fileExists = file_exists($fullPath) ? '✅' : '❌';
            echo "<td>$fileExists</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>❌ Product Images tablosu boş!</div>";
    }

    // 4. Upload klasörleri kontrolü
    echo "<h2>4. Upload Klasörleri Kontrolü</h2>";
    $uploadDirs = [
        'uploads/brands' => 'Marka logoları',
        'uploads/products' => 'Ürün resimleri',
        'uploads/categories' => 'Kategori resimleri'
    ];
    
    foreach ($uploadDirs as $dir => $description) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/' . $dir;
        if (is_dir($fullPath)) {
            $files = glob($fullPath . '/*');
            $fileCount = count($files);
            echo "<div style='background: #d4edda; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
            echo "✅ <strong>$description</strong> ($dir): $fileCount dosya<br>";
            echo "<small>$fullPath</small>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-radius: 5px;'>";
            echo "❌ <strong>$description</strong> ($dir): Klasör yok<br>";
            echo "<small>$fullPath</small>";
            echo "</div>";
        }
    }

    // 5. Örnek görsel yolu test
    echo "<h2>5. Örnek Görsel Yolu Testleri</h2>";
    $testPaths = [
        'uploads/brands/autotuner-logo.png',
        'uploads/products/autotuner-ecu-1.jpg',
        'assets/images/mreculogomini.png'
    ];
    
    foreach ($testPaths as $testPath) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/' . $testPath;
        $webPath = '/mrecuphpkopyasikopyasi6kopyasi/' . $testPath;
        $fileExists = file_exists($fullPath);
        
        echo "<div style='background: " . ($fileExists ? '#d4edda' : '#f8d7da') . "; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo ($fileExists ? '✅' : '❌') . " <strong>Test:</strong> $testPath<br>";
        echo "<strong>Tam yol:</strong> <code>$fullPath</code><br>";
        echo "<strong>Web yolu:</strong> <code>$webPath</code><br>";
        if ($fileExists) {
            echo "<img src='$webPath' style='max-width: 150px; max-height: 100px; margin-top: 10px;' onerror='this.style.display=\"none\";'>";
        }
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Hata:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔧 Olası Çözümler:</h3>";
echo "<ol>";
echo "<li><strong>Eğer dosyalar yoksa:</strong> Örnek görselleri uploads klasörlerine yükleyin</li>";
echo "<li><strong>Eğer path'ler yanlışsa:</strong> Veritabanındaki image path'lerini düzeltin</li>";
echo "<li><strong>Eğer klasörler yoksa:</strong> Upload klasörlerini oluşturun</li>";
echo "<li><strong>Mutlak path sorunu:</strong> Image src'lerinde absolute path kullanın</li>";
echo "</ol>";

echo "<h4>Hızlı Çözüm Linkleri:</h4>";
echo "<p>";
echo "<a href='#' onclick=\"location.href='create-sample-images.php'\" style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>📷 Örnek Görseller Oluştur</a>";
echo "<a href='#' onclick=\"location.href='fix-image-paths.php'\" style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🔧 Image Path'lerini Düzelt</a>";
echo "</p>";

?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
    background: #f8f9fa;
    line-height: 1.6;
}

h1, h2, h3 {
    color: #333;
}

table {
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

th {
    background: #007bff !important;
    color: white;
}

code {
    background: #f1f3f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 11px;
}

hr {
    border: none;
    border-top: 2px solid #dee2e6;
    margin: 30px 0;
}
</style>
