<?php
/**
 * Database Migration: Responsive Image Columns
 * Hero slider tablosuna mobil ve tablet image kolonlarını ekler
 */

require_once 'config/config.php';
require_once 'config/database.php';

try {
    echo "<h2>Responsive Image Columns Migration</h2>";
    echo "<p>Hero slider tablosuna mobil ve tablet image kolonları ekleniyor...</p>";
    
    // Mobile image kolonu ekle
    $sql1 = "ALTER TABLE design_sliders ADD COLUMN mobile_image VARCHAR(500) NULL AFTER background_image";
    $pdo->exec($sql1);
    echo "✅ mobile_image kolonu eklendi<br>";
    
    // Tablet image kolonu ekle
    $sql2 = "ALTER TABLE design_sliders ADD COLUMN tablet_image VARCHAR(500) NULL AFTER mobile_image";
    $pdo->exec($sql2);
    echo "✅ tablet_image kolonu eklendi<br>";
    
    // Mevcut verileri kontrol et
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM design_sliders");
    $result = $stmt->fetch();
    echo "<br>📊 Mevcut slider sayısı: {$result['count']}<br>";
    
    echo "<br>🎉 <strong>Migration başarıyla tamamlandı!</strong><br>";
    echo "<p>Artık her slider için 3 farklı resim yükleyebilirsiniz:</p>";
    echo "<ul>";
    echo "<li><strong>Desktop:</strong> 1920x800px (önerilen)</li>";
    echo "<li><strong>Tablet:</strong> 1024x600px (önerilen)</li>";
    echo "<li><strong>Mobil:</strong> 768x400px (önerilen)</li>";
    echo "</ul>";
    
    echo "<p><a href='design/sliders.php' class='btn btn-primary'>Slider Yönetimine Git</a></p>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>Hata:</strong> " . $e->getMessage();
    echo "</div>";
    
    // Eğer kolon zaten varsa bu bilgiyi ver
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<p>Kolonlar zaten mevcut. Migration daha önce çalıştırılmış.</p>";
        echo "<p><a href='design/sliders.php' class='btn btn-primary'>Slider Yönetimine Git</a></p>";
    }
}
?>

<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f8f9fa; }
    .alert { padding: 15px; margin: 10px 0; border-radius: 5px; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; }
    h2 { color: #333; }
</style>
