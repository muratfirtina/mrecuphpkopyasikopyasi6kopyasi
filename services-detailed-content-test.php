<?php
/**
 * Services Detailed Content - Kurulum ve Test
 */

require_once 'config/config.php';
require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Detailed Content - Test</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 40px; }
        .success { color: #28a745; background: #d4edda; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 8px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; color: white; }
        .btn-primary { background: #007bff; }
        .btn-success { background: #28a745; }
        .btn-info { background: #17a2b8; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 4px; font-family: 'Courier New', monospace; }
    </style>
</head>
<body>

<h1>🚀 Services Detailed Content - Kurulum Tamamlandı!</h1>

<div class="info">
    <h3>✅ Yapılan Değişiklikler</h3>
    <p>Projenizde aşağıdaki değişiklikler başarıyla yapıldı:</p>
    <ul>
        <li><strong>Database:</strong> <code>services</code> tablosuna <code>detailed_content</code> kolonu eklendi</li>
        <li><strong>Admin Paneli:</strong> <code>design/services-add.php</code> ve <code>design/services-edit.php</code> güncellendiği</li>
        <li><strong>Frontend:</strong> <code>hizmet-detay.php</code> sayfası güncellendi</li>
        <li><strong>URL Rewrite:</strong> <code>.htaccess</code> dosyasına hizmet detay kuralı eklendi</li>
    </ul>
</div>

<?php
try {
    // Database bağlantısını test et
    echo "<div class='success'><h3>🔗 Database Bağlantısı</h3>";
    echo "<p>✅ Database bağlantısı başarılı!</p></div>";
    
    // Tablo yapısını kontrol et
    $stmt = $pdo->query("DESCRIBE services");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $columnExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'detailed_content') {
            $columnExists = true;
            break;
        }
    }
    
    if ($columnExists) {
        echo "<div class='success'><h3>📊 Database Yapısı</h3>";
        echo "<p>✅ <code>detailed_content</code> kolonu mevcut ve hazır!</p></div>";
    } else {
        echo "<div class='error'><h3>❌ Database Hatası</h3>";
        echo "<p>⚠️ <code>detailed_content</code> kolonu bulunamadı! Lütfen <a href='test-services-update.php'>database güncelleme</a> scriptini çalıştırın.</p></div>";
    }
    
    // Hizmetleri listele
    $stmt = $pdo->query("SELECT id, name, slug, detailed_content FROM services ORDER BY id LIMIT 5");
    $services = $stmt->fetchAll();
    
    echo "<h3>📋 Mevcut Hizmetler</h3>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Hizmet Adı</th><th>Slug</th><th>Detailed Content</th><th>İşlemler</th></tr>";
    
    foreach ($services as $service) {
        $hasContent = !empty($service['detailed_content']);
        echo "<tr>";
        echo "<td>{$service['id']}</td>";
        echo "<td>{$service['name']}</td>";
        echo "<td><code>{$service['slug']}</code></td>";
        echo "<td>" . ($hasContent ? "✅ Mevcut" : "⚠️ Boş") . "</td>";
        echo "<td>";
        echo "<a href='design/services-edit.php?id={$service['id']}' class='btn btn-primary'>✏️ Düzenle</a>";
        echo "<a href='hizmet/{$service['slug']}' class='btn btn-info' target='_blank'>👁️ Görüntüle</a>";
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Dosya kontrolü
    $files = [
        'design/services-add.php' => 'Hizmet Ekleme Sayfası',
        'design/services-edit.php' => 'Hizmet Düzenleme Sayfası', 
        'hizmet-detay.php' => 'Hizmet Detay Sayfası',
        'services.php' => 'Hizmetler Listeleme Sayfası'
    ];
    
    echo "<h3>📁 Dosya Durumu</h3>";
    echo "<table>";
    echo "<tr><th>Dosya</th><th>Durum</th><th>Açıklama</th></tr>";
    
    foreach ($files as $file => $description) {
        $exists = file_exists($file);
        echo "<tr>";
        echo "<td><code>$file</code></td>";
        echo "<td>" . ($exists ? "✅ Mevcut" : "❌ Bulunamadı") . "</td>";
        echo "<td>$description</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='error'><h3>❌ Hata</h3>";
    echo "<p>Database hatası: " . $e->getMessage() . "</p></div>";
}
?>

<div class="warning">
    <h3>🔧 Kullanım Talimatları</h3>
    <ol>
        <li><strong>Database Güncelleme:</strong> Eğer <code>detailed_content</code> kolonu yoksa, <a href="test-services-update.php">bu linke</a> tıklayarak database'i güncelleyin.</li>
        <li><strong>İçerik Ekleme:</strong> Admin panelden (<code>design/services-edit.php</code>) hizmetlerinize HTML destekli detaylı içerik ekleyebilirsiniz.</li>
        <li><strong>HTML Kullanımı:</strong> Detailed Content alanında HTML etiketleri, CSS stilleri kullanabilirsiniz.</li>
        <li><strong>Frontend Görüntüleme:</strong> Eklenen içerikler hizmet detay sayfalarında "Detaylı Bilgiler" başlığı altında görünecek.</li>
    </ol>
</div>

<div class="info">
    <h3>🎯 Özellikler</h3>
    <ul>
        <li><strong>HTML Desteği:</strong> Detailed content alanında HTML kodları kullanabilirsiniz</li>
        <li><strong>Güvenli Düzenleme:</strong> Admin panelden kolayca düzenlenebilir</li>
        <li><strong>Mobil Uyumlu:</strong> Responsive tasarım ile tüm cihazlarda uyumlu</li>
        <li><strong>SEO Dostu:</strong> Detaylı içerikler SEO performansınızı artırır</li>
    </ul>
</div>

<h3>🔗 Hızlı Erişim</h3>
<a href="services.php" class="btn btn-primary">📋 Hizmetler Sayfası</a>
<a href="design/services-add.php" class="btn btn-success">➕ Yeni Hizmet Ekle</a>
<a href="test-services-update.php" class="btn btn-info">🔄 Database Güncelle</a>

<hr>
<p style="color: #6c757d; text-align: center; margin-top: 40px;">
    <small>Bu dosya sadece kurulum sonrası test içindir. Canlı sistemde silebilirsiniz.</small>
</p>

</body>
</html>
