<?php
/**
 * Mevcut ürünlerin currency değerlerini güncelleme
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Currency Güncelleme</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #28a745; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #dc3545; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #17a2b8; }
        .warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 5px solid #ffc107; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .btn:hover { background: #0056b3; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔄 Currency Güncelleme İşlemi</h1>";

try {
    // Veritabanı bağlantısını test et
    if (!$pdo) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }
    
    echo "<div class='success'>✅ Veritabanı bağlantısı başarılı.</div>";
    
    echo "<div class='info'>📋 Güncelleme başlatılıyor...</div>";
    
    // Mevcut currency değeri NULL olan ürünleri kontrol et
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE currency IS NULL");
    $stmt->execute();
    $nullCurrencyCount = $stmt->fetch()['count'];
    
    if ($nullCurrencyCount > 0) {
        echo "<div class='warning'>⚠️ {$nullCurrencyCount} ürünün currency değeri NULL. Bunlar TL olarak ayarlanacak.</div>";
        
        // NULL currency değerlerini TL yap
        $stmt = $pdo->prepare("UPDATE products SET currency = 'TL' WHERE currency IS NULL");
        $result = $stmt->execute();
        
        if ($result) {
            echo "<div class='success'>✅ {$nullCurrencyCount} ürünün currency değeri 'TL' olarak güncellendi.</div>";
        } else {
            echo "<div class='error'>❌ Currency güncelleme işlemi başarısız.</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ Tüm ürünlerin currency değeri zaten ayarlı.</div>";
    }
    
    // Currency dağılımını göster
    $stmt = $pdo->prepare("
        SELECT currency, COUNT(*) as count, 
               AVG(price) as avg_price,
               MIN(price) as min_price,
               MAX(price) as max_price
        FROM products 
        WHERE currency IS NOT NULL 
        GROUP BY currency 
        ORDER BY count DESC
    ");
    $stmt->execute();
    $currencyStats = $stmt->fetchAll();
    
    echo "<h2>📊 Para Birimi İstatistikleri</h2>";
    echo "<table>";
    echo "<tr><th>Para Birimi</th><th>Ürün Sayısı</th><th>Ortalama Fiyat</th><th>Min Fiyat</th><th>Max Fiyat</th></tr>";
    
    foreach ($currencyStats as $stat) {
        echo "<tr>";
        echo "<td><strong>{$stat['currency']}</strong></td>";
        echo "<td>{$stat['count']}</td>";
        echo "<td>" . number_format($stat['avg_price'], 2) . " {$stat['currency']}</td>";
        echo "<td>" . number_format($stat['min_price'], 2) . " {$stat['currency']}</td>";
        echo "<td>" . number_format($stat['max_price'], 2) . " {$stat['currency']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Son eklenen ürünleri göster
    $stmt = $pdo->prepare("
        SELECT id, name, price, sale_price, currency, created_at
        FROM products 
        ORDER BY id DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recentProducts = $stmt->fetchAll();
    
    echo "<h2>🆕 Son Eklenen Ürünler (Currency ile)</h2>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Ürün Adı</th><th>Fiyat</th><th>İndirimli Fiyat</th><th>Para Birimi</th><th>Eklenme Tarihi</th></tr>";
    
    foreach ($recentProducts as $product) {
        echo "<tr>";
        echo "<td>#{$product['id']}</td>";
        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
        echo "<td>" . number_format($product['price'], 2) . "</td>";
        echo "<td>" . ($product['sale_price'] ? number_format($product['sale_price'], 2) : '-') . "</td>";
        echo "<td><strong>{$product['currency']}</strong></td>";
        echo "<td>" . date('d.m.Y H:i', strtotime($product['created_at'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Toplam istatistik
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products");
    $stmt->execute();
    $totalProducts = $stmt->fetch()['total'];
    
    echo "<h2>📈 Genel İstatistikler</h2>";
    echo "<div class='info'>";
    echo "📦 <strong>Toplam ürün sayısı:</strong> {$totalProducts}<br>";
    echo "💱 <strong>Currency desteği:</strong> TL, USD, EUR<br>";
    echo "🎯 <strong>Güncelleme durumu:</strong> Tamamlandı<br>";
    echo "</div>";
    
    echo "<div class='success'>";
    echo "<h3>🎉 Güncelleme Tamamlandı!</h3>";
    echo "<p><strong>✅ Currency desteği başarıyla eklendi!</strong></p>";
    echo "<p>Artık admin panelinde ürün eklerken/düzenlerken para birimi seçebilirsiniz:</p>";
    echo "<ul>";
    echo "<li><strong>TL</strong> (Türk Lirası)</li>";
    echo "<li><strong>USD</strong> (Amerikan Doları)</li>";
    echo "<li><strong>EUR</strong> (Euro)</li>";
    echo "</ul>";
    echo "<p>Frontend'de fiyat 0 olan ürünlerde fiyat gösterilmeyecek.</p>";
    echo "</div>";
    
    echo "<p><a href='../admin/products.php' class='btn'>🔧 Admin Paneline Git</a> ";
    echo "<a href='../products.php' class='btn'>🛒 Ürünleri Görüntüle</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata oluştu: " . $e->getMessage() . "</div>";
    echo "<p>Lütfen şu adımları kontrol edin:</p>";
    echo "<ul>";
    echo "<li>Veritabanı bağlantı bilgileri doğru mu?</li>";
    echo "<li>Products tablosu var mı?</li>";
    echo "<li>Currency kolonu daha önce eklendi mi?</li>";
    echo "</ul>";
}

echo "</div></body></html>";
?>
