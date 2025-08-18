<?php
/**
 * Basit Alt Dosya İptal Test
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<!DOCTYPE html>\n<html><head><title>Basit İptal Test</title></head><body>";
echo "<h1>🎯 Alt Dosya İptal Sistemi Test</h1>";

// Temel kontroller
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h2>1. Dosya Varlık Kontrolleri</h2>";

$files = [
    'ajax/file-cancellation.php' => 'Ajax işleyici',
    'user/file-detail.php' => 'Kullanıcı dosya detay sayfası', 
    'includes/FileCancellationManager.php' => 'İptal yöneticisi',
    'admin/file-cancellations.php' => 'Admin iptal yönetimi'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "<p>✅ <strong>{$description}</strong> - {$file}</p>";
    } else {
        echo "<p>❌ <strong>{$description}</strong> - {$file} BULUNAMADI</p>";
    }
}
echo "</div>";

// JavaScript action kontrolü
echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h2>2. JavaScript Action Kontrolü</h2>";

if (file_exists('user/file-detail.php')) {
    $content = file_get_contents('user/file-detail.php');
    if (strpos($content, 'action=request_cancellation') !== false) {
        echo "<p>✅ <strong>Doğru action kullanılıyor:</strong> <code>action=request_cancellation</code></p>";
    } else {
        echo "<p>❌ <strong>Yanlış action kullanılıyor</strong></p>";
    }
} else {
    echo "<p>⚠ file-detail.php bulunamadı</p>";
}
echo "</div>";

// FileCancellationManager testi
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h2>3. FileCancellationManager Testi</h2>";

try {
    require_once 'includes/FileCancellationManager.php';
    $cancellationManager = new FileCancellationManager($pdo);
    echo "<p>✅ <strong>FileCancellationManager başarıyla yüklendi</strong></p>";
    
    if (method_exists($cancellationManager, 'requestCancellation')) {
        echo "<p>✅ <strong>requestCancellation metodu mevcut</strong></p>";
    } else {
        echo "<p>❌ <strong>requestCancellation metodu bulunamadı</strong></p>";
    }
} catch (Exception $e) {
    echo "<p>❌ <strong>FileCancellationManager yüklenemedi:</strong> " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test sonuçları
echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h2>🎮 Manuel Test Rehberi</h2>";
echo "<p><strong>Şimdi manuel test yapabilirsiniz:</strong></p>";
echo "<ol>";
echo "<li>Normal kullanıcı olarak giriş yapın</li>";
echo "<li>Bir dosyanın detay sayfasına gidin</li>";
echo "<li>Alt dosyalar için 'İptal' butonlarını test edin</li>";
echo "<li>Modal açılıyor mu kontrol edin</li>";
echo "<li>İptal sebebi yazıp gönderin</li>";
echo "<li>Artık 'Hata: Geçersiz işlem' almamalısınız!</li>";
echo "</ol>";

echo "<div style='background: #fff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #007bff;'>";
echo "<h4>💡 Eğer Sorun Devam Ederse:</h4>";
echo "<ul>";
echo "<li>Browser'da F12 açın → Network sekmesi</li>";
echo "<li>İptal butonuna tıklayın</li>";
echo "<li>ajax/file-cancellation.php isteğini kontrol edin</li>";
echo "<li>Request payload'da 'action=request_cancellation' görmelisiniz</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<br><a href='admin/dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Admin Paneline Dön</a>";
echo " <a href='user/files.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Kullanıcı Dosyaları</a>";
echo "</body></html>";
?>
