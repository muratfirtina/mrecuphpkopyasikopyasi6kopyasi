<?php
/**
 * Dosya İptal Sistemi Test Dosyası
 * File Cancellation System Test File
 */

echo "<h1>🎯 Dosya İptal Sistemi Test</h1>";

echo "<h2>📁 Dosya Kontrolü</h2>";

$files_to_check = [
    'sql/create_file_cancellations_table.sql',
    'sql/install_cancellation_system.php', 
    'includes/FileCancellationManager.php',
    'ajax/file-cancellation.php',
    'admin/file-cancellations.php',
    'user/cancellations.php'
];

echo "<ul>";
foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $status = $exists ? "✅" : "❌";
    echo "<li>$status $file</li>";
}
echo "</ul>";

echo "<h2>🐞 Test Linkleri</h2>";
echo "<ul>";
echo "<li><a href='sql/install_cancellation_system.php' target='_blank'>📋 Veritabanı Kurulumu</a></li>";
echo "<li><a href='debug_cancellations.php' target='_blank'>🔍 Veritabanı Kontrolü</a></li>";
echo "<li><a href='create_test_cancellation.php' target='_blank'>🧪 Test Verisi Oluştur</a></li>";
echo "<li><a href='user/files.php' target='_blank'>👤 Kullanıcı Dosyalar (Ana dosyalar için iptal butonları)</a></li>";
echo "<li><a href='user/file-detail.php' target='_blank'>👤 Dosya Detay Sayfası (Tüm dosya türleri için iptal butonları)</a></li>";
echo "<li><a href='user/cancellations.php' target='_blank'>👤 Kullanıcı İptal Talepleri</a></li>";
echo "<li><a href='admin/file-cancellations.php?debug=1' target='_blank'>🔧 Admin Debug (Sorun Tespiti)</a></li>";
echo "<li><a href='admin/file-cancellations.php' target='_blank'>🔧 Admin İptal Yönetimi</a></li>";
echo "</ul>";

echo "<h2>⚙️ Sistem Özellikleri</h2>";
echo "<ul>";
echo "<li>✅ 5 dosya türü desteklenir: upload, response, revision, additional + detay sayfası</li>";
echo "<li>✅ Ana dosyalar için iptal butonları (files.php)</li>";
echo "<li>✅ Dosya detay sayfasında tüm dosya türleri için iptal butonları (file-detail.php)</li>";
echo "<li>✅ Modern modal iptal arayüzü</li>";
echo "<li>✅ Admin onay/red sistemi</li>";
echo "<li>✅ Otomatik kredi iadesi</li>";
echo "<li>✅ Bildirim sistemi entegrasyonu</li>";
echo "<li>✅ Responsive tasarım</li>";
echo "<li>✅ GUID tabanlı güvenlik</li>";
echo "</ul>";

echo "<h2>🚀 Kurulum Talimatları</h2>";
echo "<ol>";
echo "<li><strong>Veritabanı:</strong> <a href='sql/install_cancellation_system.php'>Kurulum scriptini çalıştır</a></li>";
echo "<li><strong>Ana Dosya Testleri:</strong> Kullanıcı girişi yaparak files.php sayfasındaki İptal butonlarını dene</li>";
echo "<li><strong>Detay Sayfa Testleri:</strong> file-detail.php sayfasındaki tüm dosya türleri için iptal butonlarını dene</li>";
echo "<li><strong>Admin Yönetim:</strong> Admin girişi yaparak iptal taleplerini yönet</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>📧 Not:</strong> Bu sistem kullanıcıların tüm dosya türlerini (ana, yanıt, revize, ek dosyalar) iptal etmesine, admin onayından sonra dosyanın silinmesine ve kredi iadesinin yapılmasına olanak tanır. Dosya detay sayfasında tüm dosya türleri için ayrı ayrı iptal butonları bulunur.</p>";
?>
