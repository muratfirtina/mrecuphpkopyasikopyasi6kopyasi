<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>🔍 Database Debug</h1>";

// Slider'ları kontrol et
echo "<h2>📱 Sliders:</h2>";
try {
    $stmt = $pdo->query("SELECT id, title, background_image, is_active, created_at FROM design_sliders ORDER BY created_at DESC LIMIT 10");
    $sliders = $stmt->fetchAll();
    
    if ($sliders) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Background Image</th><th>Active</th><th>File Exists?</th><th>Created</th></tr>";
        
        foreach ($sliders as $slider) {
            $fileExists = file_exists($slider['background_image']) ? '✅' : '❌';
            $fullPath = __DIR__ . '/' . $slider['background_image'];
            $fullFileExists = file_exists($fullPath) ? '✅' : '❌';
            
            echo "<tr>";
            echo "<td>" . substr($slider['id'], 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($slider['title']) . "</td>";
            echo "<td>" . htmlspecialchars($slider['background_image']) . "</td>";
            echo "<td>" . ($slider['is_active'] ? '✅' : '❌') . "</td>";
            echo "<td>Direct: {$fileExists}<br>Full: {$fullFileExists}</td>";
            echo "<td>" . $slider['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Hiç slider bulunamadı</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Hata: " . $e->getMessage() . "</p>";
}

// Assets klasörünü kontrol et
echo "<h2>📁 Assets/Images Klasörü:</h2>";
$assetsPath = __DIR__ . '/assets/images/';
if (is_dir($assetsPath)) {
    echo "<p>✅ Klasör var: <code>{$assetsPath}</code></p>";
    
    $files = glob($assetsPath . 'slider_*.*');
    if ($files) {
        echo "<ul>";
        foreach ($files as $file) {
            $filename = basename($file);
            $size = filesize($file);
            $webPath = 'assets/images/' . $filename;
            echo "<li>";
            echo "<strong>{$filename}</strong> ({$size} bytes)<br>";
            echo "Web Path: <code>{$webPath}</code><br>";
            echo "Full Path: <code>{$file}</code>";
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>❌ Hiç slider resmi bulunamadı</p>";
    }
} else {
    echo "<p>❌ Assets/images klasörü bulunamadı: <code>{$assetsPath}</code></p>";
}

// Media files tablosunu kontrol et
echo "<h2>🎬 Media Files:</h2>";
try {
    $stmt = $pdo->query("SELECT id, filename, file_path, file_size, created_at FROM media_files WHERE file_type = 'image' ORDER BY created_at DESC LIMIT 10");
    $mediaFiles = $stmt->fetchAll();
    
    if ($mediaFiles) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Filename</th><th>File Path</th><th>Size</th><th>File Exists?</th><th>Created</th></tr>";
        
        foreach ($mediaFiles as $media) {
            $fullPath = __DIR__ . '/' . $media['file_path'];
            $fileExists = file_exists($fullPath) ? '✅' : '❌';
            
            echo "<tr>";
            echo "<td>" . substr($media['id'], 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($media['filename']) . "</td>";
            echo "<td>" . htmlspecialchars($media['file_path']) . "</td>";
            echo "<td>" . $media['file_size'] . "</td>";
            echo "<td>{$fileExists}</td>";
            echo "<td>" . $media['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Hiç medya dosyası bulunamadı</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Media files tablosu yok veya hata: " . $e->getMessage() . "</p>";
}

echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; }
    th, td { padding: 8px; text-align: left; }
    th { background: #f0f0f0; }
    code { background: #f5f5f5; padding: 2px 4px; border-radius: 3px; }
    h1, h2 { color: #333; }
</style>";
?>
