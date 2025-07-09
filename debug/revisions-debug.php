<?php
/**
 * Revisions Table Debug
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>🔧 Revisions Debug</h1>";

try {
    // 1. Revisions tablosu yapısını kontrol et
    echo "<h2>1. Revisions Tablosu Yapısı:</h2>";
    $stmt = $pdo->query("DESCRIBE revisions");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
    echo "<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td style='padding:5px;'>{$column['Field']}</td>";
        echo "<td style='padding:5px;'>{$column['Type']}</td>";
        echo "<td style='padding:5px;'>{$column['Null']}</td>";
        echo "<td style='padding:5px;'>{$column['Key']}</td>";
        echo "<td style='padding:5px;'>{$column['Default']}</td>";
        echo "<td style='padding:5px;'>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Revisions tablosundaki sample data
    echo "<h2>2. Sample Revisions Data:</h2>";
    $stmt = $pdo->query("SELECT * FROM revisions ORDER BY requested_at DESC LIMIT 5");
    $revisions = $stmt->fetchAll();
    
    if (empty($revisions)) {
        echo "<p style='color:orange;'>⚠️ Revisions tablosunda veri yok</p>";
    } else {
        echo "<pre>";
        print_r($revisions);
        echo "</pre>";
    }
    
    // 3. Test UPDATE query
    echo "<h2>3. Test UPDATE Query:</h2>";
    
    if (!empty($revisions)) {
        $testRevisionId = $revisions[0]['id'];
        echo "<p>Test Revision ID: <strong>$testRevisionId</strong></p>";
        
        // Test the exact query from updateRevisionStatus
        try {
            $stmt = $pdo->prepare("
                UPDATE revisions 
                SET admin_id = ?, status = ?, admin_notes = ?, credits_charged = ?, 
                    completed_at = CASE WHEN ? IN ('completed', 'rejected') THEN NOW() ELSE completed_at END
                WHERE id = ?
            ");
            
            // Test parametreleri
            $testParams = [
                '11111111-1111-1111-1111-111111111111', // admin_id (dummy GUID)
                'in_progress',                           // status
                'Test admin notes',                     // admin_notes
                0,                                      // credits_charged
                'in_progress',                          // status (for CASE WHEN)
                $testRevisionId                         // revision_id
            ];
            
            echo "<p><strong>Test Parametreleri:</strong></p>";
            echo "<pre>";
            print_r($testParams);
            echo "</pre>";
            
            // Dry run - execute etmeden önce query'yi test et
            $stmt->execute($testParams);
            $affectedRows = $stmt->rowCount();
            
            echo "<p style='color:green;'>✅ Test UPDATE başarılı! Etkilenen satır sayısı: $affectedRows</p>";
            
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Test UPDATE hatası:</p>";
            echo "<pre>";
            echo "Error Code: " . $e->getCode() . "\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Error Info: ";
            print_r($pdo->errorInfo());
            echo "</pre>";
        }
    }
    
    // 4. Status enum değerlerini kontrol et
    echo "<h2>4. Status ENUM Değerleri:</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM revisions LIKE 'status'");
    $statusColumn = $stmt->fetch();
    
    if ($statusColumn) {
        echo "<p><strong>Status Column Type:</strong> {$statusColumn['Type']}</p>";
        
        // ENUM değerlerini çıkar
        if (strpos($statusColumn['Type'], 'enum') !== false) {
            preg_match_all("/'([^']+)'/", $statusColumn['Type'], $matches);
            $enumValues = $matches[1];
            
            echo "<p><strong>Geçerli Status Değerleri:</strong></p>";
            echo "<ul>";
            foreach ($enumValues as $value) {
                echo "<li><code>$value</code></li>";
            }
            echo "</ul>";
            
            // in_progress değeri var mı kontrol et
            if (in_array('in_progress', $enumValues)) {
                echo "<p style='color:green;'>✅ 'in_progress' değeri ENUM'da mevcut</p>";
            } else {
                echo "<p style='color:red;'>❌ 'in_progress' değeri ENUM'da YOK!</p>";
                echo "<p><strong>Çözüm:</strong> Revisions tablosunu güncellemeniz gerekiyor.</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Debug hatası: " . $e->getMessage() . "</p>";
}

echo "<br><a href='../admin/revisions.php'>← Revisions sayfasına dön</a>";
?>
