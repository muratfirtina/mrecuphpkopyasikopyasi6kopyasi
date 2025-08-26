<?php
/**
 * MAMP mod_rewrite Test - Updated
 */

echo "<h1>MAMP mod_rewrite Test</h1>";
echo "<hr>";

// Apache modüllerini kontrol et
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    echo "<h2>Apache Modülleri:</h2>";
    
    if (in_array('mod_rewrite', $modules)) {
        echo "✅ mod_rewrite AKTIF<br>";
    } else {
        echo "❌ mod_rewrite KAPALI<br>";
    }
    
    if (in_array('mod_headers', $modules)) {
        echo "✅ mod_headers AKTIF<br>";
    } else {
        echo "❌ mod_headers KAPALI<br>";
    }
} else {
    echo "⚠️ Apache modül listesi alınamıyor (CGI/FastCGI mode olabilir)<br>";
}

// .htaccess test
echo "<h2>.htaccess Testi:</h2>";
if (file_exists('.htaccess')) {
    echo "✅ .htaccess dosyası mevcut<br>";
} else {
    echo "❌ .htaccess dosyası bulunamadı<br>";
}

// Server bilgileri
echo "<h2>Server Bilgileri:</h2>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor') . "<br>";
echo "PHP SAPI: " . php_sapi_name() . "<br>";

// Rewrite test
echo "<h2>URL Rewrite Test:</h2>";
echo "Bu sayfanın URL'si: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Query String: " . ($_SERVER['QUERY_STRING'] ?? 'Yok') . "<br>";

// Test parametresi kontrolü
if (isset($_GET['test']) && $_GET['test'] === 'simple') {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ BAŞARILI!</h3>";
    echo "<p>mod_rewrite ÇALIŞIYOR! URL rewriting aktif.</p>";
    echo "<p>Artık ürün URL'lerini aktif edebiliriz.</p>";
    echo "</div>";
    
    echo "<h3>Sonraki Adım:</h3>";
    echo "<p>mod_rewrite çalıştığına göre, ürün URL'leri için .htaccess'i güncelleyelim:</p>";
    echo "<a href='update-htaccess.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ürün URL'lerini Aktif Et</a>";
    
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ Test Gerekli</h3>";
    echo "<p>Aşağıdaki linkle test edin:</p>";
    echo "<a href='/mrecuphpkopyasikopyasi6kopyasi/test-simple' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>mod_rewrite Test Et</a>";
    echo "<p style='margin-top: 15px;'><small>Bu linke tıkladığınızda yeşil başarı mesajı görmeniz gerekiyor.</small></p>";
    echo "</div>";
    
    // Eğer rewrite çalışmıyorsa MAMP ayarları
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🛠️ mod_rewrite Çalışmıyorsa:</h3>";
    echo "<ol>";
    echo "<li><strong>MAMP Ayarları:</strong>";
    echo "<ul>";
    echo "<li>MAMP > Preferences > Apache</li>";
    echo "<li>'Apache Modules' bölümünden mod_rewrite'ı aktif edin</li>";
    echo "<li>MAMP'ı yeniden başlatın</li>";
    echo "</ul></li>";
    echo "<li><strong>httpd.conf Kontrol:</strong>";
    echo "<ul>";
    echo "<li>MAMP > Open WebStart page > Tools > phpInfo</li>";
    echo "<li>Loaded Configuration File'da httpd.conf yolunu bulun</li>";
    echo "<li>AllowOverride All olduğundan emin olun</li>";
    echo "</ul></li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Hızlı Linkler:</h3>";
echo "<ul>";
echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/' target='_blank'>Ana Sayfa</a></li>";
echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/admin/' target='_blank'>Admin Panel</a></li>";
echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/install-product-system.php' target='_blank'>Kurulum (Yeniden çalıştır)</a></li>";
echo "</ul>";
?>
