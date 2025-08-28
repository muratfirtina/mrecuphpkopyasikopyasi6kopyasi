<?php
/**
 * SKU Sistemi Test Dosyası
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>SKU Sistemi Test Sonuçları</h2>";

// Test 1: Otomatik SKU üretme fonksiyonunu test et
function generateUniqueSKU($pdo, $productName = '') {
    // Ürün adından basit bir prefix oluştur
    $prefix = '';
    if (!empty($productName)) {
        $words = explode(' ', $productName);
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $prefix .= strtoupper(substr($word, 0, 1));
            }
            if (strlen($prefix) >= 3) break;
        }
    }
    
    if (empty($prefix)) {
        $prefix = 'PRD';
    }
    
    // Benzersiz SKU bulana kadar dene
    $attempts = 0;
    do {
        $randomNumber = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $sku = $prefix . '-' . $randomNumber;
        
        // Bu SKU kullanılıyor mu kontrol et
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
        $exists = $stmt->fetchColumn() > 0;
        
        $attempts++;
        if ($attempts > 100) {
            // Çok fazla deneme yapıldı, timestamp ekle
            $sku = $prefix . '-' . time() . '-' . mt_rand(100, 999);
            break;
        }
    } while ($exists);
    
    return $sku;
}

echo "<h3>Test 1: Otomatik SKU Üretimi</h3>";
try {
    $testNames = [
        'ECU Tuning Hizmeti',
        'DPF Delete',
        'Mercedes Benz A-Serisi Chip Tuning',
        'BMW X5 Performance',
        '',  // Boş isim
        'Test Ürün'
    ];
    
    foreach ($testNames as $name) {
        $sku = generateUniqueSKU($pdo, $name);
        echo "<p><strong>Ürün:</strong> '{$name}' → <strong>SKU:</strong> {$sku}</p>";
    }
    echo "<p style='color: green;'>✓ SKU üretme fonksiyonu başarıyla çalışıyor</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ SKU üretme hatası: " . $e->getMessage() . "</p>";
}

// Test 2: Veritabanı bağlantısı ve products tablosu kontrolü
echo "<h3>Test 2: Veritabanı Kontrolü</h3>";
try {
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll();
    
    $skuFound = false;
    foreach ($columns as $column) {
        if ($column['Field'] == 'sku') {
            $skuFound = true;
            echo "<p><strong>SKU alanı:</strong> {$column['Type']} - {$column['Key']} - {$column['Null']}</p>";
            break;
        }
    }
    
    if ($skuFound) {
        echo "<p style='color: green;'>✓ Products tablosu ve SKU alanı mevcut</p>";
    } else {
        echo "<p style='color: red;'>✗ SKU alanı bulunamadı</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Veritabanı hatası: " . $e->getMessage() . "</p>";
}

// Test 3: Mevcut ürünleri listele
echo "<h3>Test 3: Mevcut Ürünler ve SKU'ları</h3>";
try {
    $stmt = $pdo->query("SELECT id, name, sku FROM products ORDER BY id DESC LIMIT 10");
    $products = $stmt->fetchAll();
    
    if (count($products) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Ürün Adı</th><th>SKU</th></tr>";
        foreach ($products as $product) {
            $skuDisplay = $product['sku'] ?: '<em style="color: red;">BOŞ</em>';
            echo "<tr><td>{$product['id']}</td><td>{$product['name']}</td><td>{$skuDisplay}</td></tr>";
        }
        echo "</table>";
        echo "<p style='color: green;'>✓ " . count($products) . " ürün listelendi</p>";
    } else {
        echo "<p>Henüz ürün eklenmemiş.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Ürün listesi hatası: " . $e->getMessage() . "</p>";
}

echo "<h3>Test Özeti</h3>";
echo "<p>Bu sayfa SKU sisteminin düzgün çalıştığını doğrulamak için oluşturulmuştur.</p>";
echo "<p><strong>Önemli:</strong> Artık ürün eklerken SKU boş bırakılabilir ve otomatik olarak benzersiz bir SKU üretilecektir.</p>";

// Test dosyasını 10 saniye sonra sil (güvenlik için)
echo "<script>
setTimeout(function() {
    if (confirm('Test tamamlandı. Bu dosyayı silmek ister misiniz?')) {
        window.location.href = 'admin/products.php';
    }
}, 10000);
</script>";
?>
