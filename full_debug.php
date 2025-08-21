<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Slider Debug Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .section { margin: 30px 0; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        img { max-width: 200px; height: auto; border: 1px solid #ccc; margin: 10px 0; }
        .url { font-family: monospace; background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Slider Sistemi Debug Test</h1>
    
    <?php
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    echo "<div class='section info'>";
    echo "<h2>📊 Database Durumu</h2>";
    
    try {
        // Slider'ları kontrol et
        $stmt = $pdo->query("SELECT * FROM design_sliders ORDER BY created_at DESC LIMIT 5");
        $sliders = $stmt->fetchAll();
        
        echo "<h3>✅ Slider'lar (" . count($sliders) . " adet)</h3>";
        if ($sliders) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Active</th><th>Image Path</th><th>File Test</th><th>URL Test</th></tr>";
            
            foreach ($sliders as $slider) {
                $shortId = substr($slider['id'], 0, 8);
                $imagePath = $slider['background_image'];
                $fullPath = __DIR__ . '/' . $imagePath;
                $fileExists = file_exists($fullPath);
                $webUrl = 'http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/' . $imagePath;
                
                echo "<tr>";
                echo "<td>{$shortId}...</td>";
                echo "<td>" . htmlspecialchars($slider['title']) . "</td>";
                echo "<td>" . ($slider['is_active'] ? '✅' : '❌') . "</td>";
                echo "<td class='url'>" . htmlspecialchars($imagePath) . "</td>";
                echo "<td>" . ($fileExists ? '✅ Var' : '❌ Yok') . "</td>";
                echo "<td><a href='{$webUrl}' target='_blank'>🔗 Test</a></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ Hiç slider bulunamadı!</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>❌ Database hatası: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Ana sayfa slider testi
    echo "<div class='section warning'>";
    echo "<h2>🏠 Ana Sayfa Slider Test</h2>";
    
    try {
        $stmt = $pdo->query("SELECT * FROM design_sliders WHERE is_active = 1 ORDER BY sort_order ASC");
        $activeSliders = $stmt->fetchAll();
        
        echo "<p><strong>Aktif Slider Sayısı:</strong> " . count($activeSliders) . "</p>";
        
        if ($activeSliders) {
            echo "<h3>🖼️ Aktif Slider'ların Resimleri</h3>";
            foreach ($activeSliders as $index => $slider) {
                $imageUrl = '/mrecuphpkopyasikopyasi6kopyasi/' . $slider['background_image'];
                echo "<div style='margin: 15px 0; padding: 10px; border: 1px solid #ccc;'>";
                echo "<h4>Slider " . ($index + 1) . ": " . htmlspecialchars($slider['title']) . "</h4>";
                echo "<p><strong>Path:</strong> <span class='url'>" . htmlspecialchars($slider['background_image']) . "</span></p>";
                echo "<p><strong>Full URL:</strong> <span class='url'>{$imageUrl}</span></p>";
                echo "<img src='{$imageUrl}' alt='Slider' onerror='this.style.border=\"2px solid red\"; this.alt=\"❌ Yüklenemedi\";' onload='this.style.border=\"2px solid green\";'>";
                echo "</div>";
            }
        } else {
            echo "<p class='error'>❌ Aktif slider bulunamadı!</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Aktif slider sorgusu hatası: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    // Assets klasör kontrolü
    echo "<div class='section info'>";
    echo "<h2>📁 Assets Klasör Kontrolü</h2>";
    
    $assetsPath = __DIR__ . '/assets/images/';
    if (is_dir($assetsPath)) {
        echo "<p class='success'>✅ Assets klasörü mevcut: <span class='url'>{$assetsPath}</span></p>";
        
        $sliderFiles = glob($assetsPath . 'slider_*.*');
        echo "<p><strong>Slider dosya sayısı:</strong> " . count($sliderFiles) . "</p>";
        
        if ($sliderFiles) {
            echo "<h3>📄 Dosya Listesi</h3>";
            echo "<ul>";
            foreach (array_slice($sliderFiles, 0, 10) as $file) {
                $filename = basename($file);
                $size = filesize($file);
                $webPath = 'assets/images/' . $filename;
                $webUrl = 'http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/' . $webPath;
                
                echo "<li>";
                echo "<strong>{$filename}</strong> (" . number_format($size) . " bytes)<br>";
                echo "Web Path: <span class='url'>{$webPath}</span><br>";
                echo "<a href='{$webUrl}' target='_blank'>🔗 Direkt Test</a>";
                echo "</li><br>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p class='error'>❌ Assets klasörü bulunamadı!</p>";
    }
    echo "</div>";
    ?>
    
    <div class="section warning">
        <h2>🧪 Hızlı Test Linkleri</h2>
        <ul>
            <li><a href="index.php" target="_blank">🏠 Ana Sayfa Test</a> - Slider'lar görünüyor mu?</li>
            <li><a href="design/sliders.php" target="_blank">⚙️ Slider Yönetimi</a> - Modal test</li>
            <li><a href="debug.php" target="_blank">🔍 Database Debug</a> - Detaylı database kontrolü</li>
        </ul>
        
        <h3>🔧 Console Test Adımları</h3>
        <ol>
            <li><strong>Ana Sayfa:</strong> F12 → Console → Network → Images filtresi</li>
            <li><strong>Modal:</strong> F12 → Console → "Yeni Slider" → Resim yükle → Console logları kontrol et</li>
            <li><strong>Debug:</strong> PHP error logları: <code>/Applications/MAMP/logs/php_error.log</code></li>
        </ol>
    </div>
    
    <div class="section success">
        <h2>✅ Çözüm Önerileri</h2>
        <ul>
            <li>Eğer resimler <strong>404 Not Found</strong> veriyor → Path problemi</li>
            <li>Eğer ana sayfada slider yok → Database query problemi</li>
            <li>Eğer modal'da önizleme yok → JavaScript/DOM problemi</li>
            <li>Eğer resim yükleme çalışmıyor → AJAX/PHP problemi</li>
        </ul>
    </div>
    
    <script>
    // JavaScript test
    console.log('🔍 Debug sayfası yüklendi');
    console.log('Current URL:', window.location.href);
    console.log('Document ready state:', document.readyState);
    </script>
</body>
</html>
