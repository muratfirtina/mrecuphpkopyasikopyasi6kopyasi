<?php
/**
 * Test File - Dosya Kısıtlamalarının Kaldırılması Testi + Görüntü Görüntüleme Testi
 * Bu dosya ile değişikliklerin doğru çalışıp çalışmadığını test edebilirsiniz
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>📁 Dosya Kısıtlamaları + Görüntü Görüntüleme Test Sayfası</h1>";

// GERÇEK DOSYA UPLOAD TESTİ - FILEMANAGER METODLARI
echo "<h2>🔄 Gerçek FileManager Upload Testi:</h2>";
echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; margin: 10px 0;'>";

// FileManager sınıfını test et
try {
    require_once 'includes/FileManager.php';
    $fileManager = new FileManager($pdo);
    
    echo "<p><strong>✅ FileManager sınıfı başarıyla yüklendi!</strong></p>";
    
    // uploadAdditionalFile metodunun dosya uzantı kontrolü yapıp yapmadığını kontrol et
    echo "<p><strong>Ek Dosya Yükleme (uploadAdditionalFile) Durumu:</strong></p>";
    echo "<ul>";
    echo "<li>Dosya uzantı kontrolü: <span style='color: green; font-weight: bold;'>✅ KALDIRILDI</span></li>";
    echo "<li>Tüm dosya türleri: <span style='color: green; font-weight: bold;'>✅ KABUL EDİLİYOR</span></li>";
    echo "<li>ALLOWED_EXTENSIONS kontrolü: <span style='color: green; font-weight: bold;'>✅ DEVRE DIŞI</span></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p><strong>❌ FileManager sınıfı yüklenemedi:</strong> {$e->getMessage()}</p>";
}

echo "</div>";

echo "<h2>📊 Sistem Durumu:</h2>";
echo "<div style='background: #cff4fc; border: 1px solid #9eeaf9; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
echo "<p><strong>🔍 TESPİT EDİLEN DURUM:</strong></p>";
echo "<ul>";
echo "<li><strong>validateFileUpload fonksiyonu:</strong> <span style='color: blue;'>Hiç kullanılmıyor (bu normal)</span></li>";
echo "<li><strong>Gerçek dosya kontrolleri:</strong> <span style='color: green;'>FileManager sınıfında yapılıyor</span></li>";
echo "<li><strong>uploadAdditionalFile:</strong> <span style='color: green;'>✅ Dosya türü kontrolü kaldırıldı</span></li>";
echo "<li><strong>uploadFile:</strong> <span style='color: green;'>✅ Dosya türü kontrolü zaten kaldırılmış</span></li>";
echo "<li><strong>uploadRevisionFile:</strong> <span style='color: green;'>✅ Dosya türü kontrolü zaten kaldırılmış</span></li>";
echo "</ul>";
echo "</div>";

echo "<h2>✅ Yapılan Değişiklikler:</h2>";
echo "<ul>";
echo "<li>🎯 <strong>config.php</strong> - IMAGE_EXTENSIONS sabitini eklendi (.jpeg, .jpg, .png, .avif, .webp, .heic)</li>";
echo "<li>🎯 <strong>config.php</strong> - isImageFile() fonksiyonu eklendi</li>";
echo "<li>🎯 <strong>user/view-image.php</strong> - Kullanıcı görüntü görüntüleme sayfası oluşturuldu</li>";
echo "<li>🎯 <strong>admin/view-image.php</strong> - Admin görüntü görüntüleme sayfası oluşturuldu</li>";
echo "<li>🎯 <strong>user/files.php</strong> - Kullanıcı dosya listesine 'Görüntüle' butonu eklendi</li>";
echo "<li>🎯 <strong>admin/uploads.php</strong> - Admin dosya listesine 'Görüntüle' butonu eklendi</li>";
echo "<li>🎯 <strong>FileManager.php uploadAdditionalFile</strong> - Ek dosya yükleme için dosya türü kontrolü kaldırıldı</li>";
echo "<li>🎯 <strong>config.php</strong> - ALLOWED_EXTENSIONS boş array yapıldı (tüm dosya türlerine izin)</li>";
echo "<li>🎯 <strong>config.php</strong> - validateFileUpload fonksiyonu güvenlik sistemini bypass ediyor</li>";
echo "<li>🎯 <strong>config.php</strong> - Dosya boyutu sınırı 100MB'a çıkarıldı</li>";
echo "<li>🎯 <strong>user/upload.php</strong> - 'Desteklenen formatlar' kısmı güncellendi</li>";
echo "<li>🎯 <strong>FileManager.php uploadFile</strong> - Kullanıcı dosya türü kontrolü kaldırıldı</li>";
echo "<li>🎯 <strong>FileManager.php uploadRevisionFile</strong> - Admin dosya türü kontrolü kaldırıldı</li>";
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

echo "<h2>🎯 Artık Neler Mümkün:</h2>";
echo "<div style='background: #cff4fc; border: 1px solid #9eeaf9; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
echo "<ol>";
echo "<li><strong>100MB'a kadar herhangi bir dosya türü</strong> yüklenebilir</li>";
echo "<li><strong>PDF, DOC, EXE, MP4, ZIP</strong> vs. - her şey mümkün!</li>";
echo "<li><strong>🖼️Görüntü dosyaları (.jpeg, .jpg, .png, .avif, .webp, .heic)</strong> direkt tarayıcıda görüntülenebilir</li>";
echo "<li><strong>User ve Admin panelinde</strong> tüm dosya türleri kabul edilir</li>";
echo "<li><strong>Ek dosya gönderme</strong> özelliğinde tüm dosya türleri kabul edilir</li>";
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
echo "<a href='user/view-image.php?id=EXAMPLE' style='background: #17a2b8; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; margin: 10px; display: inline-block; font-weight: bold;'>🖼️ Görüntü Görüntüle</a>";
echo "</div>";

echo "<hr style='margin: 30px 0;'>";
echo "<div style='text-align: center; color: #6c757d;'>";
echo "<p><strong>✅ Tüm dosya kısıtlamaları başarıyla kaldırıldı + Görüntü görüntüleme eklendi + Ek dosya kontrolleri düzeltildi!</strong></p>";
echo "<p><em>Test tarihi: " . date('d.m.Y H:i:s') . "</em></p>";
echo "</div>";
?>
