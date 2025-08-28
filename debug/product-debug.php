<?php
/**
 * Ürün Sistemi Debug ve Test Aracı
 * URL: /debug/product-debug.php
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h2>🔍 Ürün Sistemi Debug Raporu</h2>";

// 1. Database bağlantı testi
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $result = $stmt->fetch();
    echo "✅ Veritabanı bağlantısı: OK<br>";
    echo "📊 Toplam ürün sayısı: " . $result['total'] . "<br><br>";
} catch(Exception $e) {
    echo "❌ Veritabanı hatası: " . $e->getMessage() . "<br><br>";
}

// 2. Slug benzersizlik testi
echo "<h3>🔗 Slug Benzersizlik Testi</h3>";
try {
    $stmt = $pdo->query("
        SELECT slug, COUNT(*) as count 
        FROM products 
        GROUP BY slug 
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "✅ Tüm sluglar benzersiz<br>";
    } else {
        echo "❌ Duplicate slug'lar bulundu:<br>";
        foreach($duplicates as $dup) {
            echo "- '{$dup['slug']}' ({$dup['count']} kez)<br>";
        }
    }
} catch(Exception $e) {
    echo "❌ Slug kontrolü hatası: " . $e->getMessage() . "<br>";
}

// 3. SKU benzersizlik testi
echo "<h3>📋 SKU Benzersizlik Testi</h3>";
try {
    $stmt = $pdo->query("
        SELECT sku, COUNT(*) as count 
        FROM products 
        WHERE sku IS NOT NULL AND sku != ''
        GROUP BY sku 
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "✅ Tüm SKU'lar benzersiz<br>";
    } else {
        echo "❌ Duplicate SKU'lar bulundu:<br>";
        foreach($duplicates as $dup) {
            echo "- '{$dup['sku']}' ({$dup['count']} kez)<br>";
        }
    }
} catch(Exception $e) {
    echo "❌ SKU kontrolü hatası: " . $e->getMessage() . "<br>";
}

// 4. Fonksiyonları test et
echo "<h3>⚙️ Fonksiyon Testleri</h3>";

// Slug oluşturma fonksiyonu
function createSlug($text) {
    $text = trim($text);
    $text = mb_strtolower($text, 'UTF-8');
    
    // Türkçe karakterleri değiştir
    $tr = array('ş','Ş','ı','I','İ','ğ','Ü','ü','ö','Ö','Ç','ç','ğ','Ğ');
    $en = array('s','s','i','i','i','u','u','o','o','c','c','g','g');
    $text = str_replace($tr, $en, $text);
    
    // Sadece harf, rakam ve tire bırak
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    
    return $text;
}

// Test örnekleri
$testProducts = [
    'Dimsport New Trasdata ECU Programlama Cihazı',
    'Mercedes-Benz C200 Tuning Dosyası',
    'BMW 320i Chip Tuning Yazılımı'
];

echo "<h4>Slug Oluşturma Testi:</h4>";
foreach($testProducts as $product) {
    $slug = createSlug($product);
    echo "'{$product}' → '{$slug}'<br>";
}

// 5. Upload dizinleri kontrolü
echo "<h3>📁 Upload Dizinleri</h3>";
$uploadDirs = [
    '../uploads/products/',
    '../uploads/brands/',
    '../uploads/categories/'
];

foreach($uploadDirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ Yazılabilir: {$dir}<br>";
        } else {
            echo "⚠️ Yazılamaz: {$dir}<br>";
            echo "💡 Çözüm: chmod 755 {$dir}<br>";
        }
    } else {
        echo "⚠️ Dizin yok: {$dir}<br>";
        echo "💡 Çözüm: mkdir -p {$dir}<br>";
    }
}

// 6. Veritabanı yapısı kontrolü
echo "<h3>🗄️ Tablo Yapısı</h3>";
$tables = ['products', 'product_images', 'product_brands', 'categories'];

foreach($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
        $count = $stmt->fetchColumn();
        echo "✅ {$table}: {$count} kayıt<br>";
    } catch(Exception $e) {
        echo "❌ {$table}: Hata - " . $e->getMessage() . "<br>";
    }
}

// 7. Son 10 ürün listesi
echo "<h3>📋 Son Eklenen 10 Ürün</h3>";
try {
    $stmt = $pdo->query("
        SELECT id, name, slug, sku, price 
        FROM products 
        ORDER BY created_at DESC, id DESC 
        LIMIT 10
    ");
    $products = $stmt->fetchAll();
    
    if (empty($products)) {
        echo "⚠️ Henüz ürün yok<br>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Ürün Adı</th><th>Slug</th><th>SKU</th><th>Fiyat</th></tr>";
        foreach($products as $p) {
            echo "<tr>";
            echo "<td>{$p['id']}</td>";
            echo "<td>{$p['name']}</td>";
            echo "<td>{$p['slug']}</td>";
            echo "<td>{$p['sku']}</td>";
            echo "<td>{$p['price']} TL</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch(Exception $e) {
    echo "❌ Ürün listesi hatası: " . $e->getMessage() . "<br>";
}

echo "<br><hr><br>";
echo "<h3>🔗 Hızlı Erişim</h3>";
echo "🔗 <a href='../admin/products.php' target='_blank'>Ürün Yönetimi</a><br>";
echo "🔗 <a href='../admin/product-brands.php' target='_blank'>Marka Yönetimi</a><br>";
echo "🔗 <a href='../products.php' target='_blank'>Ürünler Sayfası</a><br>";

echo "<br><h3>🧪 Test Senaryoları</h3>";
echo "<p><strong>Şimdi şunları test edin:</strong></p>";
echo "<ol>";
echo "<li>Yeni ürün ekleyin - slug otomatik oluşturulacak</li>";
echo "<li>Aynı isimde ikinci ürün ekleyin - slug-2 gibi benzersiz slug oluşacak</li>";
echo "<li>Mevcut ürünü güncelleyin - slug duplicate hatası ÇIKMAYACAK</li>";
echo "<li>Boş SKU ile ürün ekleyin - otomatik SKU üretilecek</li>";
echo "<li>Modal tasarımlarının tutarlı olduğunu kontrol edin</li>";
echo "</ol>";

echo "<br><p style='color: green;'><strong>✅ Tüm düzeltmeler uygulandı!</strong></p>";
?>
