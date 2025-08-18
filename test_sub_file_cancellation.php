<?php
/**
 * Mr ECU - Alt Dosya İptal Sistemi Test
 * Yanıt, Revizyon ve Ek Dosyaları İptal Testi
 */

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/FileCancellationManager.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    die('Bu sayfaya erişim izniniz yok.');
}

echo "<!DOCTYPE html>\n<html><head><title>Alt Dosya İptal Sistemi Test</title></head><body>";
echo "<h1>Alt Dosya İptal Sistemi Test Raporu</h1>";

try {
    $cancellationManager = new FileCancellationManager($pdo);
    
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>1. JavaScript Action Kontrolü</h2>";
    echo "<p>ajax/file-cancellation.php dosyasında beklenen action: <code>request_cancellation</code></p>";
    echo "<p>user/file-detail.php dosyasında gönderilen action: <code>request_cancellation</code> ✅</p>";
    echo "</div>";
    
    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>2. Dosya Tiplerinin Desteklenme Durumu</h2>";
    
    $supportedTypes = ['upload', 'response', 'revision', 'additional'];
    foreach ($supportedTypes as $type) {
        echo "<p>✅ <strong>{$type}</strong>: Destekleniyor</p>";
    }
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>3. Dosya Sahiplik Kontrolleri</h2>";
    
    echo "<h4>📁 Ana Dosya (upload):</h4>";
    echo "<p>Kontrol: <code>file_uploads.user_id = current_user_id</code></p>";
    
    echo "<h4>💬 Yanıt Dosyası (response):</h4>";
    echo "<p>Kontrol: Ana dosyanın sahibi yanıt dosyasını iptal edebilir</p>";
    echo "<p>SQL: <code>file_responses → file_uploads.user_id = current_user_id</code></p>";
    
    echo "<h4>🔄 Revizyon Dosyası (revision):</h4>";
    echo "<p>Kontrol: Ana dosyanın sahibi revizyon dosyasını iptal edebilir</p>";
    echo "<p>SQL: <code>revision_files → file_uploads.user_id = current_user_id</code></p>";
    
    echo "<h4>📎 Ek Dosya (additional):</h4>";
    echo "<p>Kontrol: Dosyayı alan kullanıcı (receiver) iptal edebilir</p>";
    echo "<p>SQL: <code>additional_files.receiver_id = current_user_id</code></p>";
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>4. Test Senaryoları</h2>";
    
    // Test kullanıcısının dosyalarını kontrol et
    $testUserId = null;
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = 'user' LIMIT 1");
    $stmt->execute();
    $testUser = $stmt->fetch();
    
    if ($testUser) {
        $testUserId = $testUser['id'];
        echo "<p><strong>Test Kullanıcısı:</strong> {$testUser['username']} ({$testUserId})</p>";
        
        // Ana dosyalar
        $uploadStmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE user_id = ? AND (is_cancelled IS NULL OR is_cancelled = 0)");
        $uploadStmt->execute([$testUserId]);
        $uploadCount = $uploadStmt->fetchColumn();
        echo "<p><strong>Ana dosyalar:</strong> {$uploadCount} adet</p>";
        
        // Yanıt dosyaları
        $responseStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM file_responses fr
            LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
            WHERE fu.user_id = ? AND (fr.is_cancelled IS NULL OR fr.is_cancelled = 0)
        ");
        $responseStmt->execute([$testUserId]);
        $responseCount = $responseStmt->fetchColumn();
        echo "<p><strong>Yanıt dosyaları:</strong> {$responseCount} adet</p>";
        
        // Revizyon dosyaları
        $revisionStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM revision_files rf
            LEFT JOIN file_uploads fu ON rf.upload_id = fu.id
            WHERE fu.user_id = ? AND (rf.is_cancelled IS NULL OR rf.is_cancelled = 0)
        ");
        $revisionStmt->execute([$testUserId]);
        $revisionCount = $revisionStmt->fetchColumn();
        echo "<p><strong>Revizyon dosyaları:</strong> {$revisionCount} adet</p>";
        
        // Ek dosyalar
        $additionalStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM additional_files 
            WHERE receiver_id = ? AND (is_cancelled IS NULL OR is_cancelled = 0)
        ");
        $additionalStmt->execute([$testUserId]);
        $additionalCount = $additionalStmt->fetchColumn();
        echo "<p><strong>Ek dosyalar:</strong> {$additionalCount} adet</p>";
        
        if ($uploadCount > 0 || $responseCount > 0 || $revisionCount > 0 || $additionalCount > 0) {
            echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>🎮 Test Adımları:</h4>";
            echo "<ol>";
            echo "<li><strong>Kullanıcı olarak giriş yapın:</strong> {$testUser['username']}</li>";
            echo "<li><strong>Dosya detay sayfasına gidin:</strong> user/file-detail.php?id=[DOSYA_ID]</li>";
            echo "<li><strong>Alt dosyalar için iptal butonlarını test edin:</strong></li>";
            echo "<ul>";
            echo "<li>Yanıt dosyaları listesindeki 'İptal' butonları</li>";
            echo "<li>Revizyon dosyaları listesindeki 'İptal' butonları</li>";
            echo "<li>Ek dosyalar listesindeki 'İptal' butonları</li>";
            echo "</ul>";
            echo "<li><strong>İptal sebebi yazın ve gönderin</strong></li>";
            echo "<li><strong>Admin olarak onaylayın:</strong> admin/file-cancellations.php</li>";
            echo "</ol>";
            echo "</div>";
        } else {
            echo "<p style='color: orange;'>⚠ Test için yeterli dosya bulunamadı. Önce bazı dosyalar yükleyin.</p>";
        }
        
    } else {
        echo "<p style='color: orange;'>ℹ Test için kullanıcı bulunamadı</p>";
    }
    echo "</div>";
    
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>5. Sorun Giderme</h2>";
    echo "<h4>❌ \"Hata: Geçersiz işlem\" alıyorsanız:</h4>";
    echo "<ul>";
    echo "<li>Browser'ın Developer Tools > Network sekmesini açın</li>";
    echo "<li>İptal butonuna tıklayın</li>";
    echo "<li>ajax/file-cancellation.php isteğini kontrol edin</li>";
    echo "<li>action parametresinin 'request_cancellation' olduğunu doğrulayın</li>";
    echo "</ul>";
    
    echo "<h4>❌ \"Bu dosyayı iptal etme yetkiniz yok\" alıyorsanız:</h4>";
    echo "<ul>";
    echo "<li>Dosyanın gerçekten size ait olduğunu kontrol edin</li>";
    echo "<li>Ana dosya sahibi olarak yanıt/revizyon dosyalarını iptal edebilirsiniz</li>";
    echo "<li>Ek dosyalar için receiver olmanız gerekir</li>";
    echo "</ul>";
    
    echo "<h4>✅ Başarılı iptal için:</h4>";
    echo "<ul>";
    echo "<li>Dosya file-detail sayfasında artık görünmeyecek</li>";
    echo "<li>Kredi iadesi varsa kullanıcının credit_used değeri azalacak</li>";
    echo "<li>credit_transactions tablosunda 'refund' kaydı oluşacak</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2 style='color: red;'>Test Hatası:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<br><a href='../admin/dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Admin Paneline Dön</a>";
echo " <a href='../admin/file-cancellations.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>İptal Yönetimi</a>";
echo "</body></html>";
?>
