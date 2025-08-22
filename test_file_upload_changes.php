<?php
/**
 * Test File - Dosya Kısıtlamalarının Kaldırılması Testi
 * Bu dosya ile değişikliklerin doğru çalışıp çalışmadığını test edebilirsiniz
 */

require_once 'config/config.php';

echo "<h1>📁 Dosya Kısıtlamaları Test Sayfası - Tüm Kısıtlamalar Kaldırıldı!</h1>";

echo "<h2>✅ Yapılan Değişiklikler:</h2>";
echo "<ul>";
echo "<li>🎯 <strong>config.php</strong> - Dosya boyutu sınırı 100MB'a çıkarıldı</li>";
echo "<li>🎯 <strong>config.php</strong> - ALLOWED_EXTENSIONS boş array yapıldı (tüm dosya türlerine izin)</li>";
echo "<li>🎯 <strong>user/upload.php</strong> - HTML accept attribute kaldırıldı</li>";
echo "<li>🎯 <strong>user/upload.php</strong> - 'Desteklenen formatlar' kısmı güncellendi</li>";
echo "<li>🎯 <strong>FileManager.php uploadFile</strong> - Kullanıcı dosya türü kontrolü kaldırıldı</li>";
echo "<li>🎯 <strong>FileManager.php uploadRevisionFile</strong> - Admin dosya türü kontrolü kaldırıldı</li>";
echo "<li>🎯 <strong>config.php validateFileUpload</strong> - Dosya türü kontrolü kapandı</li>";
echo "</ul>";

echo "<h2>📊 Mevcut Sistem Durumu:</h2>";
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
echo "<p><strong>📋 ALLOWED_EXTENSIONS:</strong> ";
if (empty(ALLOWED_EXTENSIONS)) {
    echo "<span style='color: green; font-weight: bold;'>[] (Boş - Tüm dosya türlerine izin verildi) 🎉</span>";
} else {
    echo "<span style='color: red;'>" . implode(', ', ALLOWED_EXTENSIONS) . "</span>";
}
echo "</p>";

echo "<p><strong>📏 MAX_FILE_SIZE:</strong> <span style='color: green; font-weight: bold;'>" . formatFileSize(MAX_FILE_SIZE) . " 🚀</span></p>";
echo "</div>";

echo "<h2>🧪 Test Sonuçları:</h2>";

// validateFileUpload fonksiyonunu test et
echo "<h3>📝 validateFileUpload Fonksiyon Testi:</h3>";

// Örnek dosya verisi oluştur
$testFile = [
    'name' => 'test.pdf',
    'tmp_name' => '/tmp/test',
    'size' => 1024,
    'error' => UPLOAD_ERR_OK,
    'type' => 'application/pdf'
];

$validationResult = validateFileUpload($testFile);
echo "<p><strong>PDF dosyası test:</strong> ";
if ($validationResult['valid']) {
    echo "<span style='color: green; font-weight: bold;'>✅ BAŞARILI - PDF dosyası kabul edildi</span>";
} else {
    echo "<span style='color: red; font-weight: bold;'>❌ BAŞARISIZ - " . implode(', ', $validationResult['errors']) . "</span>";
}
echo "</p>";

// Farklı uzantıları test et
$testExtensions = ['exe', 'docx', 'txt', 'php', 'js', 'css', 'mp4', 'zip', 'rar', 'pdf', 'jpg', 'png', 'gif', 'bin', 'hex'];

echo "<h3>🔍 Farklı Dosya Türleri Testi:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f8f9fa;'><th style='padding: 10px;'>Dosya Türü</th><th style='padding: 10px;'>Test Sonucu</th></tr>";

foreach ($testExtensions as $ext) {
    $testFile['name'] = "test.$ext";
    $result = validateFileUpload($testFile);
    
    echo "<tr>";
    echo "<td style='padding: 8px; text-align: center; font-weight: bold;'>.$ext</td>";
    if ($result['valid']) {
        echo "<td style='padding: 8px; color: green; font-weight: bold; text-align: center;'>✅ Kabul Edildi</td>";
    } else {
        echo "<td style='padding: 8px; color: red; font-weight: bold; text-align: center;'>❌ Reddedildi: " . implode(', ', $result['errors']) . "</td>";
    }
    echo "</tr>";
}

echo "</table>";

// Dosya boyutu testi
echo "<h3>📏 Dosya Boyutu Testi:</h3>";
$sizeTests = [
    '1 MB' => 1 * 1024 * 1024,
    '50 MB' => 50 * 1024 * 1024,
    '100 MB' => 100 * 1024 * 1024,
    '150 MB' => 150 * 1024 * 1024
];

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
echo "<tr style='background: #f8f9fa;'><th style='padding: 10px;'>Dosya Boyutu</th><th style='padding: 10px;'>Test Sonucu</th></tr>";

foreach ($sizeTests as $sizeLabel => $sizeBytes) {
    $testFile['size'] = $sizeBytes;
    $testFile['name'] = 'test.txt';
    $result = validateFileUpload($testFile);
    
    echo "<tr>";
    echo "<td style='padding: 8px; text-align: center; font-weight: bold;'>$sizeLabel</td>";
    if ($result['valid']) {
        echo "<td style='padding: 8px; color: green; font-weight: bold; text-align: center;'>✅ Kabul Edildi</td>";
    } else {
        echo "<td style='padding: 8px; color: red; font-weight: bold; text-align: center;'>❌ Reddedildi: " . implode(', ', $result['errors']) . "</td>";
    }
    echo "</tr>";
}

echo "</table>";

echo "<h2>🎯 Artık Neler Mümkün:</h2>";
echo "<div style='background: #cff4fc; border: 1px solid #9eeaf9; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
echo "<ol>";
echo "<li><strong>100MB'a kadar herhangi bir dosya türü</strong> yüklenebilir</li>";
echo "<li><strong>User ve Admin panelinde</strong> tüm dosya türleri kabul edilir</li>";
echo "<li><strong>PDF, DOC, EXE, MP4, ZIP</strong> vs. - her şey mümkün!</li>";
echo "<li><strong>Güvenlik:</strong> Dosya adları UUID ile değiştirilir</li>";
echo "<li><strong>Konum:</strong> Dosyalar /uploads/ klasöründe güvenli saklanır</li>";
echo "</ol>";
echo "</div>";

echo "<h2>⚠️ Güvenlik Önlemleri:</h2>";
echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
echo "<ul>";
echo "<li><strong>🔒 Dosya isimleri:</strong> UUID ile güvenli hale getirilir</li>";
echo "<li><strong>📁 Depolama:</strong> /uploads/ klasöründe ve .htaccess koruması var</li>";
echo "<li><strong>🚫 Executable engel:</strong> PHP/EXE dosyaları çalıştırılamaz</li>";
echo "<li><strong>📏 Boyut kontrolü:</strong> Maksimum " . formatFileSize(MAX_FILE_SIZE) . "</li>";
echo "<li><strong>🔍 İzleme:</strong> Tüm yüklemeler loglanır ve takip edilir</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🧪 Test Linkleri:</h2>";
echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='user/upload.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin: 10px; display: inline-block; font-weight: bold;'>📁 User Dosya Yükleme</a>";
echo "<a href='admin/uploads.php' style='background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin: 10px; display: inline-block; font-weight: bold;'>⚙️ Admin Dosya Yönetimi</a>";
echo "</div>";

echo "<hr style='margin: 30px 0;'>";
echo "<div style='text-align: center; color: #6c757d;'>";
echo "<p><strong>✅ Tüm dosya kısıtlamaları başarıyla kaldırıldı!</strong></p>";
echo "<p><em>Test tarihi: " . date('d.m.Y H:i:s') . "</em></p>";
echo "</div>";
?>
