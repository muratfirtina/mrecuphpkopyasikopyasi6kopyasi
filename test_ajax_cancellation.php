<?php
/**
 * Mr ECU - Alt Dosya İptal Sistemi Debug Test
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<!DOCTYPE html>\n<html><head><title>Alt Dosya İptal Debug Test</title></head><body>";
echo "<h1>🔧 Alt Dosya İptal Sistemi Debug Test</h1>";

// Ajax dosyası kontrolü
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h2>1. Ajax Dosyası Kontrolü</h2>";

$ajaxFile = 'ajax/file-cancellation.php';
if (file_exists($ajaxFile)) {
    echo "<p>✅ <strong>ajax/file-cancellation.php</strong> dosyası mevcut</p>";
    
    // Dosya içeriğini oku ve action kontrolü yap
    $content = file_get_contents($ajaxFile);
    if (strpos($content, 'request_cancellation') !== false) {
        echo "<p>✅ request_cancellation action tanımlı</p>";
    } else {
        echo "<p>❌ request_cancellation action tanımlı DEĞİL</p>";
    }
    
    if (strpos($content, 'FileCancellationManager') !== false) {
        echo "<p>✅ FileCancellationManager include edilmiş</p>";
    } else {
        echo "<p>❌ FileCancellationManager include edilmemiş</p>";
    }
} else {
    echo "<p>❌ <strong>ajax/file-cancellation.php</strong> dosyası mevcut DEĞİL</p>";
}
echo "</div>";

// JavaScript Action Test
echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h2>2. JavaScript Action Test</h2>";

$fileDetailFile = 'user/file-detail.php';
if (file_exists($fileDetailFile)) {
    echo "<p>✅ <strong>user/file-detail.php</strong> dosyası mevcut</p>";
    
    $content = file_get_contents($fileDetailFile);
    if (strpos($content, 'action=request_cancellation') !== false) {
        echo "<p>✅ JavaScript'te doğru action kullanılıyor: <code>action=request_cancellation</code></p>";
    } else {
        echo "<p>❌ JavaScript'te yanlış action kullanılıyor</p>";
    }
    
    // İptal butonlarını kontrol et
    $cancelButtonCount = substr_count($content, 'requestCancellation');
    echo "<p>✅ {$cancelButtonCount} adet iptal butonu bulundu</p>";
    
    if (strpos($content, "onclick=\"requestCancellation") !== false) {
        echo "<p>✅ İptal butonları doğru tanımlanmış</p>";
    } else {
        echo "<p>❌ İptal butonları yanlış tanımlanmış</p>";
    }
} else {
    echo "<p>❌ <strong>user/file-detail.php</strong> dosyası mevcut DEĞİL</p>";
}
echo "</div>";

// Dosya Sahiplik Test
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h2>3. FileCancellationManager Test</h2>";

try {
    require_once 'includes/FileCancellationManager.php';
    $cancellationManager = new FileCancellationManager($pdo);
    echo "<p>✅ FileCancellationManager başarıyla yüklendi</p>";
    
    // requestCancellation metodunu kontrol et
    if (method_exists($cancellationManager, 'requestCancellation')) {
        echo "<p>✅ requestCancellation metodu mevcut</p>";
    } else {
        echo "<p>❌ requestCancellation metodu mevcut DEĞİL</p>";
    }
    
    // Test kullanıcısı var mı kontrol et
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'user' LIMIT 1");
    $stmt->execute();
    $testUser = $stmt->fetch();
    
    if ($testUser) {
        echo "<p>✅ Test kullanıcısı bulundu: <strong>{$testUser['username']}</strong> ({$testUser['id']})</p>";
        
        // Test kullanıcısının dosyaları var mı?
        $fileStmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE user_id = ?");
        $fileStmt->execute([$testUser['id']]);
        $fileCount = $fileStmt->fetchColumn();
        echo "<p>📁 Test kullanıcısının dosya sayısı: {$fileCount}</p>";
        
        if ($fileCount > 0) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>🎮 Manuel Test Yapabilirsiniz:</h4>";
            echo "<ol>";
            echo "<li><strong>Kullanıcı olarak giriş yapın:</strong> {$testUser['username']}</li>";
            echo "<li><strong>Dosya detay sayfasına gidin:</strong> Bir dosyanızın detayına girin</li>";
            echo "<li><strong>İptal butonlarını test edin:</strong> Ana dosya, yanıt, revizyon veya ek dosya iptal butonlarına tıklayın</li>";
            echo "<li><strong>Modal açılıyor mu kontrol edin</strong></li>";
            echo "<li><strong>İptal sebebi yazın ve gönderin</strong></li>";
            echo "<li><strong>Hata alıyor musunuz kontrol edin</strong></li>";
            echo "</ol>";
            echo "</div>";
        }
    } else {
        echo "<p>⚠ Test kullanıcısı bulunamadı</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ FileCancellationManager yüklenirken hata: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Ajax Test Aracı
echo "<div style='background: #f0f8f0; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h2>4. Canlı Ajax Test Aracı</h2>";
echo "<p>Bu araçla gerçek ajax isteği gönderebilirsiniz:</p>";

echo '<div id="ajaxTestResult" style="margin: 10px 0; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 5px; display: none;"></div>';

echo '<button onclick="testAjaxCall()" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
    Ajax Test Et
</button>';

echo "<script>
function testAjaxCall() {
    const resultDiv = document.getElementById('ajaxTestResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<i>Test ediliyor...</i>';
    
    fetch('ajax/file-cancellation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=test&test=true'
    })
    .then(response => response.text())
    .then(data => {
        resultDiv.innerHTML = '<strong>Ajax Response:</strong><br><pre>' + data + '</pre>';
    })
    .catch(error => {
        resultDiv.innerHTML = '<strong style=\"color: red;\">Ajax Error:</strong><br>' + error.message;
    });
}
</script>";
echo "</div>";

// Sonuç ve Öneriler
echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
echo "<h2>5. Sonuç ve Öneriler</h2>";
echo "<p><strong>✅ Sistem hazır görünüyor! Şu adımları takip edin:</strong></p>";
echo "<ol>";
echo "<li><strong>Manuel test yapın:</strong> Yukarıdaki bilgilerle manuel test edin</li>";
echo "<li><strong>Ajax testi çalıştırın:</strong> 'Ajax Test Et' butonuna tıklayın</li>";
echo "<li><strong>Gerçek dosya testi:</strong> Kullanıcı olarak giriş yapıp gerçek dosya iptal testi yapın</li>";
echo "<li><strong>Admin onayı:</strong> admin/file-cancellations.php sayfasından iptal talebini onaylayın</li>";
echo "<li><strong>Kredi kontrolü:</strong> admin/credits.php sayfasından kredi iadesini kontrol edin</li>";
echo "</ol>";

echo "<div style='background: #fff; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #007bff;'>";
echo "<h4>📝 Eğer Hala 'Geçersiz İşlem' Hatası Alıyorsanız:</h4>";
echo "<ul>";
echo "<li>Browser'da F12 açın, Network sekmesine gidin</li>";
echo "<li>İptal butonuna tıklayın</li>";
echo "<li>ajax/file-cancellation.php isteğini bulun</li>";
echo "<li>Request payload'da 'action=request_cancellation' olduğunu kontrol edin</li>";
echo "<li>Response'da ne döndüğünü kontrol edin</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<br><a href='admin/dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Admin Paneline Dön</a>";
echo " <a href='admin/file-cancellations.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>İptal Yönetimi</a>";
echo " <a href='user/files.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Kullanıcı Dosyaları</a>";
echo "</body></html>";
?>
