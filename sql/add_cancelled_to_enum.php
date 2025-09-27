<?php
/**
 * Status ENUM'ına 'cancelled' Değeri Ekle
 */

require_once __DIR__ . '/../config/database.php';

echo "<h2>Status ENUM Güncelleme</h2>";
echo "<p>Status kolonuna 'cancelled' değeri ekleniyor...</p>";

try {
    // Önce mevcut ENUM değerlerini al
    $stmt = $pdo->query("SHOW COLUMNS FROM file_uploads WHERE Field = 'status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$column) {
        throw new Exception("Status kolonu bulunamadı!");
    }
    
    echo "<div style='padding: 10px; background: #f5f5f5; margin: 10px 0;'>";
    echo "<strong>Mevcut Status Tipi:</strong> " . htmlspecialchars($column['Type']);
    echo "</div>";
    
    // ENUM değerlerini parse et
    if (preg_match("/^enum\((.+)\)$/i", $column['Type'], $matches)) {
        $values = array_map(function($value) {
            return trim($value, "'");
        }, explode(',', $matches[1]));
        
        echo "<div style='padding: 10px; background: #e3f2fd; margin: 10px 0;'>";
        echo "<strong>Mevcut değerler:</strong> " . implode(', ', $values);
        echo "</div>";
        
        // 'cancelled' zaten var mı?
        if (in_array('cancelled', $values)) {
            echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #e8f5e9;'>";
            echo "✅ 'cancelled' değeri zaten tanımlı. Güncelleme gerekmiyor.";
            echo "</div>";
        } else {
            // 'cancelled' ekle
            $values[] = 'cancelled';
            $newEnum = "ENUM('" . implode("','", $values) . "')";
            
            echo "<div style='padding: 10px; background: #fff3e0; margin: 10px 0;'>";
            echo "<strong>Yeni ENUM değerleri:</strong> " . htmlspecialchars($newEnum);
            echo "</div>";
            
            // ALTER TABLE komutu
            $sql = "ALTER TABLE file_uploads MODIFY COLUMN status {$newEnum}";
            
            echo "<div style='padding: 10px; background: #f5f5f5; margin: 10px 0;'>";
            echo "<strong>SQL Komutu:</strong><br>";
            echo "<code>" . htmlspecialchars($sql) . "</code>";
            echo "</div>";
            
            // Güncelleme yap
            $pdo->exec($sql);
            
            echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #e8f5e9; margin: 10px 0;'>";
            echo "✅ Status kolonuna 'cancelled' değeri başarıyla eklendi!";
            echo "</div>";
            
            // Şimdi iptal edilmiş dosyaları güncelle
            echo "<hr><h3>İptal Edilmiş Dosyaları Güncelleme</h3>";
            
            $updateStmt = $pdo->prepare("
                UPDATE file_uploads 
                SET status = 'cancelled' 
                WHERE is_cancelled = 1 
                AND status != 'cancelled'
            ");
            
            $updateStmt->execute();
            $updatedCount = $updateStmt->rowCount();
            
            echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #e8f5e9; margin: 10px 0;'>";
            echo "✅ <strong>{$updatedCount} adet</strong> dosyanın status'u 'cancelled' olarak güncellendi!";
            echo "</div>";
            
            echo "<p><a href='../admin/uploads.php?status=cancelled'>İptal Edilmiş Dosyaları Görüntüle</a></p>";
        }
        
    } else {
        echo "<div style='color: orange; padding: 10px; border: 1px solid orange; background: #fff3e0;'>";
        echo "⚠️ Status kolonu ENUM değil. Tipi: " . htmlspecialchars($column['Type']);
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; background: #ffebee;'>";
    echo "❌ Hata: " . htmlspecialchars($e->getMessage());
    echo "</div>";
    error_log('ENUM update error: ' . $e->getMessage());
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
    }
    h2, h3 {
        color: #1976d2;
    }
    code {
        background: #263238;
        color: #aed581;
        padding: 2px 6px;
        border-radius: 3px;
        display: inline-block;
    }
</style>
