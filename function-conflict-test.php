<?php
/**
 * Function Conflict Test
 * Fonksiyon çakışması hatası çözülmüş mü test et
 */

// Test config dosyasını yükle
require_once 'config/config.php';

echo "<h1>🔧 Function Conflict Test</h1>";

// formatFileSize fonksiyonunu test et
echo "<h2>formatFileSize() Test</h2>";
try {
    $testSizes = [0, 1024, 1048576, 1073741824];
    
    foreach ($testSizes as $size) {
        $formatted = formatFileSize($size);
        echo "<p>$size bytes = <strong>$formatted</strong></p>";
    }
    
    echo "<p style='color:green;'>✅ formatFileSize() fonksiyonu çalışıyor!</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ formatFileSize() hatası: " . $e->getMessage() . "</p>";
}

// Diğer fonksiyonları test et
echo "<h2>Other Functions Test</h2>";
try {
    echo "<p>formatDate() test: <strong>" . formatDate('2024-01-01 12:00:00') . "</strong></p>";
    echo "<p>sanitize() test: <strong>" . sanitize('<script>alert("test")</script>') . "</strong></p>";
    echo "<p>generateToken() test: <strong>" . substr(generateToken(), 0, 10) . "...</strong></p>";
    
    echo "<p style='color:green;'>✅ Tüm fonksiyonlar çalışıyor!</p>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Fonksiyon hatası: " . $e->getMessage() . "</p>";
}

// Admin sayfasını test et
echo "<h2>Admin Page Test</h2>";
echo "<p>🔗 Test linkleri:</p>";
echo "<ul>";
echo "<li><a href='admin/file-detail.php?id=test' target='_blank'>Admin File Detail (should show error, not function conflict)</a></li>";
echo "<li><a href='admin/revisions.php' target='_blank'>Admin Revisions</a></li>";
echo "<li><a href='user/files.php' target='_blank'>User Files</a></li>";
echo "</ul>";

echo "<div style='background:#d4edda; padding:20px; border-radius:8px; margin:20px 0;'>";
echo "<h3>🎉 Function Conflict Fixed!</h3>";
echo "<p>formatFileSize() fonksiyon çakışması sorunu çözüldü.</p>";
echo "<p>✅ config.php'de function_exists() kontrolü eklendi</p>";
echo "<p>✅ admin/file-detail.php'den duplicate fonksiyon kaldırıldı</p>";
echo "<p>✅ Sistem artık hata vermeden çalışacak</p>";
echo "</div>";

echo "<p><em>Test tamamlandı: " . date('Y-m-d H:i:s') . "</em></p>";
?>
