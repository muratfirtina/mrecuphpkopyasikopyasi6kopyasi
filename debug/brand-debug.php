<?php
/**
 * Quick Debug Tool - Marka Sistemi
 * URL: /debug/brand-debug.php
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h2>🔍 Marka Sistemi Debug Raporu</h2>";

// 1. Database bağlantı testi
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM product_brands");
    $result = $stmt->fetch();
    echo "✅ Veritabanı bağlantısı: OK<br>";
    echo "📊 Toplam marka sayısı: " . $result['total'] . "<br><br>";
} catch(Exception $e) {
    echo "❌ Veritabanı hatası: " . $e->getMessage() . "<br><br>";
}

// 2. Tablo yapısı kontrolü
try {
    $stmt = $pdo->query("DESCRIBE product_brands");
    $columns = $stmt->fetchAll();
    echo "✅ product_brands tablosu mevcut<br>";
    echo "📋 Tablo sütunları:<br>";
    foreach($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
    }
    echo "<br>";
} catch(Exception $e) {
    echo "❌ Tablo yapısı hatası: " . $e->getMessage() . "<br><br>";
}

// 3. Örnek kayıt kontrolü
try {
    $stmt = $pdo->query("SELECT * FROM product_brands LIMIT 1");
    $sample = $stmt->fetch();
    if ($sample) {
        echo "✅ Örnek kayıt mevcut<br>";
        echo "🔍 İlk kayıt: " . $sample['name'] . "<br><br>";
    } else {
        echo "⚠️ Henüz kayıt yok<br><br>";
    }
} catch(Exception $e) {
    echo "❌ Kayıt kontrolü hatası: " . $e->getMessage() . "<br><br>";
}

// 4. Upload dizini kontrolü  
$uploadDir = '../uploads/brands/';
if (is_dir($uploadDir)) {
    if (is_writable($uploadDir)) {
        echo "✅ Upload dizini yazılabilir: " . $uploadDir . "<br>";
    } else {
        echo "⚠️ Upload dizini yazılamaz: " . $uploadDir . "<br>";
        echo "💡 Çözüm: chmod 755 " . $uploadDir . "<br>";
    }
} else {
    echo "⚠️ Upload dizini yok: " . $uploadDir . "<br>";
    echo "💡 Çözüm: mkdir -p " . $uploadDir . "<br>";
}

echo "<br><hr><br>";
echo "🔗 <a href='../admin/product-brands.php'>Marka Yönetim Sayfası</a><br>";
echo "🔗 <a href='brand-system-improvements.php'>İyileştirme Önerileri</a>";
?>
