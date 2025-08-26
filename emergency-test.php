<?php
/**
 * Emergency Test - System Check
 */
echo "<h1>🔥 Emergency Test - " . date('Y-m-d H:i:s') . "</h1>";
echo "<div style='background: #d4edda; padding: 15px; margin: 20px 0; border-radius: 5px; color: #155724;'>";
echo "<h2>✅ BAŞARILI!</h2>";
echo "<p>PHP çalışıyor! .htaccess sorunu çözüldü.</p>";
echo "</div>";

echo "<h2>Sistem Bilgileri:</h2>";
echo "<ul>";
echo "<li>PHP Version: " . PHP_VERSION . "</li>";
echo "<li>Current Time: " . date('Y-m-d H:i:s') . "</li>";
echo "<li>Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</li>";
echo "</ul>";

echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/emergency-test.php' target='_blank'>Bu Sayfa (Çalışıyor)</a></li>";
echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/' target='_blank'>Ana Sayfa Test</a></li>";
echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/admin/' target='_blank'>Admin Panel Test</a></li>";
echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/install-product-system.php' target='_blank'>Kurulum</a></li>";
echo "</ul>";

echo "<div style='background: #fff3cd; padding: 15px; margin: 20px 0; border-radius: 5px; color: #856404;'>";
echo "<h3>⚠️ Sonraki Adımlar:</h3>";
echo "<p>1. Yukarıdaki linkleri test edin</p>";
echo "<p>2. Hangi link çalışmıyorsa söyleyin</p>";
echo "<p>3. Sonra güvenli .htaccess kuracağız</p>";
echo "</div>";
?>
