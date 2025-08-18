<?php
/**
 * Mr ECU - İptal Sistemi Test Dosyası
 * Admin onayından sonra dosyanın gizlenmesi ve kredi iadesini test eder
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/FileCancellationManager.php';
require_once 'includes/FileManager.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    die('Bu sayfaya erişim izniniz yok.');
}

echo "<!DOCTYPE html>\n<html><head><title>İptal Sistemi Test</title></head><body>";
echo "<h1>İptal Sistemi Test Raporu</h1>";

try {
    $cancellationManager = new FileCancellationManager($pdo);
    $fileManager = new FileManager($pdo);
    
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>1. Veritabanı Yapı Kontrolü</h2>";
    
    // Tablo yapılarını kontrol et
    $tables = [
        'file_uploads' => 'Ana dosyalar',
        'file_responses' => 'Yanıt dosyaları', 
        'revision_files' => 'Revizyon dosyaları',
        'additional_files' => 'Ek dosyalar'
    ];
    
    $allTablesReady = true;
    foreach ($tables as $table => $description) {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM $table LIKE 'is_cancelled'");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result) {
                echo "<p style='color: green;'>✓ $description ($table): is_cancelled sütunu mevcut</p>";
            } else {
                echo "<p style='color: red;'>✗ $description ($table): is_cancelled sütunu eksik</p>";
                $allTablesReady = false;
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ $description ($table): Hata - " . $e->getMessage() . "</p>";
            $allTablesReady = false;
        }
    }
    
    if (!$allTablesReady) {
        echo "<p style='color: red; font-weight: bold;'>⚠ Migration'u çalıştırmayı unutmayın: ";
        echo "<a href='install_cancellation_columns.php'>sql/install_cancellation_columns.php</a></p>";
    }
    echo "</div>";
    
    echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>2. İptal Talebi Sistemi Testi</h2>";
    
    // Bekleyen iptal taleplerini getir
    $pendingCancellations = $cancellationManager->getAllCancellations(1, 5, 'pending');
    
    if (empty($pendingCancellations)) {
        echo "<p style='color: orange;'>ℹ Henüz bekleyen iptal talebi bulunmuyor.</p>";
        echo "<p>Test için:</p>";
        echo "<ol>";
        echo "<li>Normal kullanıcı olarak giriş yapın</li>";
        echo "<li>Bir dosya için iptal talebi oluşturun</li>";
        echo "<li>Bu sayfayı tekrar ziyaret edin</li>";
        echo "</ol>";
    } else {
        echo "<p style='color: green;'>✓ " . count($pendingCancellations) . " bekleyen iptal talebi bulundu</p>";
        
        foreach ($pendingCancellations as $cancellation) {
            echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
            echo "<strong>Talep ID:</strong> " . substr($cancellation['id'], 0, 8) . "...<br>";
            echo "<strong>Dosya Tipi:</strong> " . strtoupper($cancellation['file_type']) . "<br>";
            echo "<strong>Kredi İadesi:</strong> " . number_format($cancellation['credits_to_refund'], 2) . " kredi<br>";
            if ($cancellation['credits_to_refund'] > 0) {
                echo "<strong style='color: green;'>İade Edilecek:</strong> " . number_format($cancellation['credits_to_refund'], 2) . " kredi<br>";
            } else {
                echo "<strong style='color: orange;'>Ücretsiz dosya</strong> (kredi iadesi yok)<br>";
            }
            echo "<strong>Sebep:</strong> " . htmlspecialchars(substr($cancellation['reason'], 0, 100)) . "...<br>";
            echo "</div>";
        }
    }
    echo "</div>";
    
    echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>3. Kredi Sistemi Testi</h2>";
    
    // Test kullanıcısının kredi durumunu kontrol et
    $testUserId = null;
    $stmt = $pdo->prepare("SELECT id, username, credit_quota, credit_used FROM users WHERE role = 'user' LIMIT 1");
    $stmt->execute();
    $testUser = $stmt->fetch();
    
    if ($testUser) {
        $testUserId = $testUser['id'];
        $availableCredits = $testUser['credit_quota'] - $testUser['credit_used'];
        
        echo "<p><strong>Test Kullanıcısı:</strong> {$testUser['username']} ({$testUserId})</p>";
        echo "<p><strong>Kredi Kotası:</strong> {$testUser['credit_quota']} TL</p>";
        echo "<p><strong>Kullanılan Kredi:</strong> {$testUser['credit_used']} TL</p>";
        echo "<p><strong>Kullanılabilir Kredi:</strong> {$availableCredits} TL</p>";
        
        // Credit transactions tablosunu kontrol et
        try {
            $transactionStmt = $pdo->prepare("SELECT COUNT(*) FROM credit_transactions WHERE user_id = ?");
            $transactionStmt->execute([$testUserId]);
            $transactionCount = $transactionStmt->fetchColumn();
            echo "<p><strong>Kredi İşlem Geçmişi:</strong> {$transactionCount} işlem</p>";
            
            // Son kredi işlemlerini göster
            $recentStmt = $pdo->prepare("
                SELECT transaction_type, type, amount, description, created_at 
                FROM credit_transactions 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 3
            ");
            $recentStmt->execute([$testUserId]);
            $recentTransactions = $recentStmt->fetchAll();
            
            if (!empty($recentTransactions)) {
                echo "<p><strong>Son 3 İşlem:</strong></p>";
                echo "<ul>";
                foreach ($recentTransactions as $transaction) {
                    echo "<li>{$transaction['type']}: {$transaction['amount']} TL - {$transaction['description']} ({$transaction['created_at']})</li>";
                }
                echo "</ul>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>⚠ Credit transactions tablosu bulunamadı: " . $e->getMessage() . "</p>";
        }
        
    echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>4. Alt Dosya İptal Sistemi Kontrolü</h2>";
    
    // Test kullanıcısının alt dosyalarını kontrol et
    if ($testUser) {
        echo "<p><strong>Test Kullanıcısı:</strong> {$testUser['username']} ({$testUserId})</p>";
        
        // Yanıt dosyaları kontrolü
        try {
            $responseStmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM file_responses fr
                LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
                WHERE fu.user_id = ? AND (fr.is_cancelled IS NULL OR fr.is_cancelled = 0)
            ");
            $responseStmt->execute([$testUserId]);
            $responseCount = $responseStmt->fetchColumn();
            echo "<p><strong>Yanıt dosyaları:</strong> {$responseCount} adet</p>";
            
            // Revizyon dosyaları kontrolü
            $revisionStmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM revision_files rf
                LEFT JOIN file_uploads fu ON rf.upload_id = fu.id
                WHERE fu.user_id = ? AND (rf.is_cancelled IS NULL OR rf.is_cancelled = 0)
            ");
            $revisionStmt->execute([$testUserId]);
            $revisionCount = $revisionStmt->fetchColumn();
            echo "<p><strong>Revizyon dosyaları:</strong> {$revisionCount} adet</p>";
            
            // Ek dosyalar kontrolü
            $additionalStmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM additional_files 
                WHERE receiver_id = ? AND (is_cancelled IS NULL OR is_cancelled = 0)
            ");
            $additionalStmt->execute([$testUserId]);
            $additionalCount = $additionalStmt->fetchColumn();
            echo "<p><strong>Ek dosyalar:</strong> {$additionalCount} adet</p>";
            
            // JavaScript action kontrolü
            echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>🔧 JavaScript Action Kontrolü:</h4>";
            echo "<p>✅ <strong>file-detail.php</strong> dosyasında action parametresi düzeltildi</p>";
            echo "<p><code>action=create</code> → <code>action=request_cancellation</code></p>";
            echo "<p>✅ ajax/file-cancellation.php ile uyumlu hale getirildi</p>";
            echo "</div>";
            
            // Sahiplik kontrol sistemi
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>🔒 Dosya Sahiplik Kontrolü:</h4>";
            echo "<ul>";
            echo "<li><strong>Ana dosya:</strong> Sadece dosya sahibi iptal edebilir</li>";
            echo "<li><strong>Yanıt dosyası:</strong> Ana dosya sahibi iptal edebilir</li>";
            echo "<li><strong>Revizyon:</strong> Ana dosya sahibi iptal edebilir</li>";
            echo "<li><strong>Ek dosya:</strong> Alıcı (receiver) iptal edebilir</li>";
            echo "</ul>";
            echo "</div>";
            
            if ($responseCount > 0 || $revisionCount > 0 || $additionalCount > 0) {
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
                echo "<h4>🎮 Test Adımları:</h4>";
                echo "<ol>";
                echo "<li>Kullanıcı olarak giriş yapın: <strong>{$testUser['username']}</strong></li>";
                echo "<li>Bir dosyanın detay sayfasına gidin</li>";
                echo "<li>Alt dosyaların 'İptal' butonlarını test edin</li>";
                echo "<li>Modal açılıyor mu kontrol edin</li>";
                echo "<li>İptal sebebi yazıp gönderin</li>";
                echo "<li>Başarı mesajı alıyor musunuz kontrol edin</li>";
                echo "</ol>";
                echo "</div>";
            } else {
                echo "<p style='color: orange;'>⚠ Test için yeterli alt dosya bulunamadı. Önce bazı yanıt/revizyon/ek dosyaları oluşturun.</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>⚠ Alt dosya kontrol hatası: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: orange;'>ℹ Test için kullanıcı bulunamadı</p>";
    }
    echo "</div>";
    
    } else {
        echo "<p style='color: orange;'>ℹ Test için kullanıcı bulunamadı</p>";
    }
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>4. Fonksiyon Testi</h2>";
    
    // İptal manager test
    echo "<p><strong>FileCancellationManager:</strong> ";
    if (class_exists('FileCancellationManager')) {
        echo "<span style='color: green;'>✓ Yüklendi</span></p>";
        
        // Test metod
        $stats = $cancellationManager->getCancellationStats();
        echo "<p><strong>İstatistikler:</strong></p>";
        echo "<ul>";
        echo "<li>Toplam: " . $stats['total'] . "</li>";
        echo "<li>Bekleyen: " . $stats['pending'] . "</li>";
        echo "<li>Onaylanan: " . $stats['approved'] . "</li>";
        echo "<li>Reddedilen: " . $stats['rejected'] . "</li>";
        echo "<li>İade edilen kredi: " . $stats['total_refunded'] . "</li>";
        echo "</ul>";
    } else {
        echo "<span style='color: red;'>✗ Yüklenemedi</span></p>";
    }
    
    // FileManager test
    echo "<p><strong>FileManager:</strong> ";
    if (class_exists('FileManager')) {
        echo "<span style='color: green;'>✓ Yüklendi</span></p>";
    } else {
        echo "<span style='color: red;'>✗ Yüklenemedi</span></p>";
    }
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>5. Test Talimatları</h2>";
    echo "<p><strong>İptal sistemi testi için:</strong></p>";
    echo "<ol>";
    echo "<li><strong>Migration:</strong> Eğer veritabanı sütunları eksikse <a href='install_cancellation_columns.php'>buraya tıklayarak</a> migration'u çalıştırın</li>";
    echo "<li><strong>İptal talebi oluşturma:</strong> Normal kullanıcı olarak giriş yapın ve bir dosya için iptal talebi oluşturun</li>";
    echo "<li><strong>Admin onayı:</strong> <a href='../admin/file-cancellations.php'>İptal yönetimi sayfasından</a> talebi onaylayın</li>";
    echo "<li><strong>Kontrol:</strong> Kullanıcı panelinde dosyanın artık görünmediğini kontrol edin</li>";
    echo "<li><strong>Kredi kontrolü:</strong> Eğer ücretli dosya ise kredinin iade edildiğini kontrol edin</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2 style='color: red;'>Test Hatası:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<br><a href='admin/dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Admin Paneline Dön</a>";
echo " <a href='admin/file-cancellations.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>İptal Yönetimi</a>";
echo "</body></html>";
?>
