<?php
/**
 * Mr ECU - Image Path Düzeltme
 * Görsel yollarını düzeltir ve örnek görseller oluşturur
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>🔧 Image Path Düzeltme ve Örnek Görsel Oluşturma</h1>";
echo "<hr>";

try {
    // 1. Upload klasörlerini oluştur
    echo "<h2>1. Upload Klasörleri Oluşturuluyor...</h2>";
    $uploadDirs = [
        'uploads',
        'uploads/brands',
        'uploads/products', 
        'uploads/categories'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "✅ $dir klasörü oluşturuldu<br>";
            } else {
                echo "❌ $dir klasörü oluşturulamadı<br>";
            }
        } else {
            echo "⚠️ $dir klasörü zaten mevcut<br>";
        }
    }

    // 2. Örnek brand logoları oluştur/güncelle
    echo "<h2>2. Marka Logoları Güncelleniyor...</h2>";
    
    // SVG logo oluşturma fonksiyonu
    function createSVGLogo($brandName, $color = '#007bff') {
        $initials = strtoupper(substr($brandName, 0, 2));
        return "<?xml version='1.0' encoding='UTF-8'?>
<svg width='200' height='80' viewBox='0 0 200 80' xmlns='http://www.w3.org/2000/svg'>
    <rect width='200' height='80' rx='8' fill='{$color}'/>
    <text x='100' y='30' text-anchor='middle' fill='white' font-family='Arial, sans-serif' font-size='14' font-weight='bold'>{$initials}</text>
    <text x='100' y='50' text-anchor='middle' fill='white' font-family='Arial, sans-serif' font-size='12'>{$brandName}</text>
    <rect x='10' y='60' width='30' height='4' fill='white' opacity='0.8'/>
    <rect x='50' y='60' width='20' height='4' fill='white' opacity='0.6'/>
    <rect x='80' y='60' width='40' height='4' fill='white' opacity='0.4'/>
</svg>";
    }
    
    $brands = [
        ['name' => 'AutoTuner', 'slug' => 'autotuner', 'color' => '#e74c3c'],
        ['name' => 'KESS V2', 'slug' => 'kess-v2', 'color' => '#2ecc71'],
        ['name' => 'KTM Flash', 'slug' => 'ktm-flash', 'color' => '#f39c12'],
        ['name' => 'PCM Flash', 'slug' => 'pcm-flash', 'color' => '#9b59b6'],
        ['name' => 'CMD Flash', 'slug' => 'cmd-flash', 'color' => '#1abc9c']
    ];
    
    foreach ($brands as $brand) {
        $logoPath = 'uploads/brands/' . $brand['slug'] . '-logo.svg';
        $logoContent = createSVGLogo($brand['name'], $brand['color']);
        
        // Logo dosyasını oluştur
        if (file_put_contents($logoPath, $logoContent)) {
            echo "✅ {$brand['name']} logosu oluşturuldu: $logoPath<br>";
            
            // Veritabanında güncelle
            try {
                $stmt = $pdo->prepare("UPDATE product_brands SET logo = ? WHERE slug = ?");
                $stmt->execute([$logoPath, $brand['slug']]);
                echo "&nbsp;&nbsp;&nbsp;&nbsp;📝 Veritabanında güncellendi<br>";
            } catch (Exception $e) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;⚠️ Veritabanı güncellemesi başarısız: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ {$brand['name']} logosu oluşturulamadı<br>";
        }
    }

    // 3. Örnek ürün resimleri oluştur
    echo "<h2>3. Ürün Resimleri Oluşturuluyor...</h2>";
    
    // SVG ürün resmi oluşturma fonksiyonu
    function createProductImage($productName, $color = '#007bff', $width = 400, $height = 300) {
        $shortName = substr($productName, 0, 15) . (strlen($productName) > 15 ? '...' : '');
        return "<?xml version='1.0' encoding='UTF-8'?>
<svg width='{$width}' height='{$height}' viewBox='0 0 {$width} {$height}' xmlns='http://www.w3.org/2000/svg'>
    <defs>
        <linearGradient id='grad' x1='0%' y1='0%' x2='100%' y2='100%'>
            <stop offset='0%' style='stop-color:{$color};stop-opacity:1' />
            <stop offset='100%' style='stop-color:#2c3e50;stop-opacity:1' />
        </linearGradient>
    </defs>
    <rect width='{$width}' height='{$height}' fill='url(#grad)'/>
    <rect x='50' y='80' width='300' height='120' rx='10' fill='white' opacity='0.9'/>
    <text x='200' y='130' text-anchor='middle' fill='#2c3e50' font-family='Arial, sans-serif' font-size='16' font-weight='bold'>{$shortName}</text>
    <text x='200' y='150' text-anchor='middle' fill='#7f8c8d' font-family='Arial, sans-serif' font-size='12'>ECU Programming Device</text>
    <circle cx='100' cy='250' r='15' fill='white' opacity='0.8'/>
    <circle cx='140' cy='250' r='15' fill='white' opacity='0.6'/>
    <circle cx='180' cy='250' r='15' fill='white' opacity='0.4'/>
    <rect x='250' y='235' width='60' height='30' rx='15' fill='white' opacity='0.7'/>
    <text x='280' y='255' text-anchor='middle' fill='{$color}' font-family='Arial, sans-serif' font-size='14' font-weight='bold'>ECU</text>
</svg>";
    }
    
    // Aktif ürünleri getir
    $stmt = $pdo->query("SELECT id, name, slug FROM products WHERE is_active = 1 LIMIT 10");
    $products = $stmt->fetchAll();
    
    $colors = ['#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22', '#95a5a6'];
    
    foreach ($products as $index => $product) {
        $color = $colors[$index % count($colors)];
        
        // Ana ürün resmi oluştur
        $imagePath = 'uploads/products/' . $product['slug'] . '-main.svg';
        $imageContent = createProductImage($product['name'], $color);
        
        if (file_put_contents($imagePath, $imageContent)) {
            echo "✅ {$product['name']} ana resmi oluşturuldu<br>";
            
            // Veritabanında product_images tablosuna ekle
            try {
                // Mevcut resmi kontrol et
                $stmt = $pdo->prepare("SELECT id FROM product_images WHERE product_id = ? AND is_primary = 1");
                $stmt->execute([$product['id']]);
                $existingImage = $stmt->fetch();
                
                if ($existingImage) {
                    // Güncelle
                    $stmt = $pdo->prepare("UPDATE product_images SET image_path = ? WHERE id = ?");
                    $stmt->execute([$imagePath, $existingImage['id']]);
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;📝 Mevcut resim güncellendi<br>";
                } else {
                    // Yeni resim ekle
                    $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, alt_text, is_primary, sort_order) VALUES (?, ?, ?, 1, 0)");
                    $stmt->execute([$product['id'], $imagePath, $product['name']]);
                    echo "&nbsp;&nbsp;&nbsp;&nbsp;📝 Yeni resim eklendi<br>";
                }
            } catch (Exception $e) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;⚠️ Veritabanı işlemi başarısız: " . $e->getMessage() . "<br>";
            }
            
            // Ek resimler oluştur (2-3 adet)
            for ($i = 1; $i <= 2; $i++) {
                $extraImagePath = 'uploads/products/' . $product['slug'] . "-{$i}.svg";
                $extraColor = $colors[($index + $i) % count($colors)];
                $extraImageContent = createProductImage($product['name'] . " View {$i}", $extraColor, 400, 300);
                
                if (file_put_contents($extraImagePath, $extraImageContent)) {
                    // Veritabanına ekle
                    try {
                        $stmt = $pdo->prepare("SELECT id FROM product_images WHERE product_id = ? AND image_path = ?");
                        $stmt->execute([$product['id'], $extraImagePath]);
                        
                        if (!$stmt->fetch()) {
                            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, alt_text, is_primary, sort_order) VALUES (?, ?, ?, 0, ?)");
                            $stmt->execute([$product['id'], $extraImagePath, $product['name'] . " - View {$i}", $i]);
                        }
                    } catch (Exception $e) {
                        // Sessizce geç
                    }
                }
            }
        } else {
            echo "❌ {$product['name']} resmi oluşturulamadı<br>";
        }
    }

    // 4. Veritabanı güncellik kontrolü
    echo "<h2>4. Veritabanı Durumu Kontrolü</h2>";
    
    // Marka logo sayısı
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_brands WHERE logo IS NOT NULL AND logo != ''");
    $brandLogos = $stmt->fetch()['count'];
    echo "✅ Logo'su olan marka sayısı: $brandLogos<br>";
    
    // Ürün resim sayısı
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_images");
    $productImages = $stmt->fetch()['count'];
    echo "✅ Toplam ürün resmi: $productImages<br>";
    
    // Ana resmi olan ürün sayısı
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_images WHERE is_primary = 1");
    $primaryImages = $stmt->fetch()['count'];
    echo "✅ Ana resmi olan ürün sayısı: $primaryImages<br>";

    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724;'>🎉 İşlem Tamamlandı!</h3>";
    echo "<p style='color: #155724;'>Görsel dosyaları oluşturuldu ve veritabanı güncellendi. Artık ürün ve marka görselleri görüntülenmelidir.</p>";
    echo "</div>";

    echo "<h4>Test Linkleri:</h4>";
    echo "<p>";
    echo "<a href='/' target='_blank' style='background: #007bff; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🏠 Ana Sayfa</a>";
    echo "<a href='/urunler' target='_blank' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>📦 Ürünler</a>";
    echo "<a href='debug-images.php' target='_blank' style='background: #ffc107; color: #212529; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>🔍 Debug</a>";
    echo "</p>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ <strong>Hata:</strong> " . $e->getMessage();
    echo "</div>";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 900px;
    margin: 20px auto;
    padding: 20px;
    background: #f8f9fa;
    line-height: 1.6;
}

h1, h2, h3 {
    color: #333;
}

hr {
    border: none;
    border-top: 2px solid #dee2e6;
    margin: 30px 0;
}

a {
    text-decoration: none;
}

a:hover {
    opacity: 0.8;
}
</style>
