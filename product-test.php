<?php
/**
 * Ürün Test - Normal URL ile
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>🛍️ Ürün Test Sayfası</h1>";
echo "<hr>";

try {
    // Ürünleri listele
    $stmt = $pdo->query("
        SELECT p.*, 
               pb.name as brand_name,
               c.name as category_name
        FROM products p 
        LEFT JOIN product_brands pb ON p.brand_id = pb.id 
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1
        ORDER BY p.name
        LIMIT 5
    ");
    $products = $stmt->fetchAll();
    
    if (!empty($products)) {
        echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border-radius: 5px; color: #155724;'>";
        echo "<h2>✅ Ürünler Bulundu!</h2>";
        echo "<p>" . count($products) . " ürün listeleniyor:</p>";
        echo "</div>";
        
        echo "<h3>📦 Mevcut Ürünler:</h3>";
        echo "<div style='display: grid; gap: 15px;'>";
        
        foreach ($products as $product) {
            echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: white;'>";
            echo "<h4>" . htmlspecialchars($product['name']) . "</h4>";
            
            if ($product['brand_name']) {
                echo "<p><strong>Marka:</strong> " . htmlspecialchars($product['brand_name']) . "</p>";
            }
            
            if ($product['category_name']) {
                echo "<p><strong>Kategori:</strong> " . htmlspecialchars($product['category_name']) . "</p>";
            }
            
            echo "<p><strong>Fiyat:</strong> " . number_format($product['price'], 2) . " TL</p>";
            
            // Normal URL ile link
            echo "<div style='margin-top: 10px;'>";
            echo "<a href='product-detail.php?id=" . $product['id'] . "' style='background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Normal URL ile Görüntüle</a>";
            echo "<a href='product-detail.php?slug=" . $product['slug'] . "' style='background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;'>Slug ile Görüntüle</a>";
            echo "</div>";
            
            // SEO URL (henüz çalışmayacak)
            echo "<p style='margin-top: 10px; color: #6c757d;'>";
            echo "<strong>Gelecek SEO URL:</strong> <code>/urun/" . htmlspecialchars($product['slug']) . "</code> (henüz aktif değil)";
            echo "</p>";
            echo "</div>";
        }
        echo "</div>";
        
    } else {
        echo "<div style='background: #fff3cd; padding: 15px; margin: 20px 0; border-radius: 5px; color: #856404;'>";
        echo "<h2>⚠️ Ürün Bulunamadı</h2>";
        echo "<p>Önce kurulum yapmalısınız:</p>";
        echo "<a href='install-product-system.php' style='background: #ffc107; color: #000; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Kurulumu Çalıştır</a>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; margin: 20px 0; border-radius: 5px; color: #721c24;'>";
    echo "<h2>❌ Veritabanı Hatası</h2>";
    echo "<p>Hata: " . $e->getMessage() . "</p>";
    echo "<p>Önce kurulum yapın:</p>";
    echo "<a href='install-product-system.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Kurulumu Çalıştır</a>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔗 Hızlı Linkler:</h3>";
echo "<ul>";
echo "<li><a href='/' target='_blank'>Ana Sayfa</a></li>";
echo "<li><a href='admin/' target='_blank'>Admin Panel</a></li>";
echo "<li><a href='install-product-system.php' target='_blank'>Kurulum</a></li>";
echo "<li><a href='emergency-test.php' target='_blank'>Emergency Test</a></li>";
echo "</ul>";
?>
