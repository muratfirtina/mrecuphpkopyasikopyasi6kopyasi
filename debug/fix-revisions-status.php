<?php
/**
 * Fix Revisions Table - Add in_progress Status
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>🔧 Revisions Tablosu Düzeltme</h1>";

try {
    echo "<h2>1. Mevcut Status ENUM Değerleri:</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM revisions LIKE 'status'");
    $statusColumn = $stmt->fetch();
    echo "<p><strong>Önce:</strong> " . $statusColumn['Type'] . "</p>";
    
    echo "<h2>2. Status ENUM'una 'in_progress' Ekleniyor...</h2>";
    
    // ALTER TABLE komutu ile ENUM'u güncelle
    $alterQuery = "
        ALTER TABLE revisions 
        MODIFY COLUMN status ENUM('pending','in_progress','completed','rejected') 
        DEFAULT 'pending'
    ";
    
    $pdo->exec($alterQuery);
    echo "<p style='color:green;'>✅ Status ENUM başarıyla güncellendi!</p>";
    
    echo "<h2>3. Güncellenmiş Status ENUM Değerleri:</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM revisions LIKE 'status'");
    $statusColumn = $stmt->fetch();
    echo "<p><strong>Sonra:</strong> " . $statusColumn['Type'] . "</p>";
    
    // ENUM değerlerini çıkar ve göster
    if (strpos($statusColumn['Type'], 'enum') !== false) {
        preg_match_all("/'([^']+)'/", $statusColumn['Type'], $matches);
        $enumValues = $matches[1];
        
        echo "<p><strong>Geçerli Status Değerleri:</strong></p>";
        echo "<ul>";
        foreach ($enumValues as $value) {
            $color = ($value === 'in_progress') ? 'color:green; font-weight:bold;' : '';
            echo "<li style='$color'><code>$value</code>";
            if ($value === 'in_progress') echo " <span style='color:green;'>← YENİ!</span>";
            echo "</li>";
        }
        echo "</ul>";
        
        // in_progress değeri var mı kontrol et
        if (in_array('in_progress', $enumValues)) {
            echo "<p style='color:green; font-size:18px; font-weight:bold;'>🎉 'in_progress' değeri başarıyla eklendi!</p>";
        }
    }
    
    echo "<h2>4. Test UPDATE Query (Tekrar):</h2>";
    
    // Test query'sini tekrar çalıştır
    $stmt = $pdo->query("SELECT * FROM revisions WHERE status = 'pending' ORDER BY requested_at DESC LIMIT 1");
    $testRevision = $stmt->fetch();
    
    if ($testRevision) {
        $testRevisionId = $testRevision['id'];
        echo "<p>Test Revision ID: <strong>$testRevisionId</strong></p>";
        
        try {
            $stmt = $pdo->prepare("
                UPDATE revisions 
                SET admin_id = ?, status = ?, admin_notes = ?, credits_charged = ?, 
                    completed_at = CASE WHEN ? IN ('completed', 'rejected') THEN NOW() ELSE completed_at END
                WHERE id = ?
            ");
            
            $testParams = [
                '11111111-1111-1111-1111-111111111111', // admin_id (dummy GUID)
                'in_progress',                           // status
                'Test admin notes - in_progress works!', // admin_notes
                0,                                      // credits_charged
                'in_progress',                          // status (for CASE WHEN)
                $testRevisionId                         // revision_id
            ];
            
            $stmt->execute($testParams);
            $affectedRows = $stmt->rowCount();
            
            echo "<p style='color:green; font-size:16px; font-weight:bold;'>🎉 Test UPDATE başarılı! Etkilenen satır sayısı: $affectedRows</p>";
            
            // Test kaydını geri döndür
            $stmt = $pdo->prepare("UPDATE revisions SET status = 'pending', admin_id = NULL, admin_notes = NULL WHERE id = ?");
            $stmt->execute([$testRevisionId]);
            echo "<p><small>Test kaydı eski haline döndürüldü.</small></p>";
            
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Test UPDATE hatası:</p>";
            echo "<pre>";
            echo "Error Code: " . $e->getCode() . "\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Tablo güncelleme hatası: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🚀 Sonraki Adımlar:</h2>";
echo "<ol>";
echo "<li>✅ Revisions tablosu güncellendi</li>";
echo "<li>🔄 Şimdi <a href='../admin/revisions.php'>Revisions sayfasına</a> dönün</li>";
echo "<li>🎯 'Onayla' butonunu tekrar test edin</li>";
echo "</ol>";

echo "<br><a href='../admin/revisions.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📋 Revisions Sayfasına Dön</a>";
?>
