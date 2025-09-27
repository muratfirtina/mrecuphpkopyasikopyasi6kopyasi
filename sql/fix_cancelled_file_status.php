<?php
/**
 * Mr ECU - Migration Script
 * Eski iptal edilmiş dosyaların status kolonunu düzelt
 * 
 * Bu script, is_cancelled = 1 olan ama status != 'cancelled' olan dosyaları düzeltir
 */

require_once __DIR__ . '/../config/database.php';

echo "<h2>Dosya İptal Status Düzeltme Migration</h2>";
echo "<p>Eski iptal edilmiş dosyaların status kolonunu düzeltiyor...</p>";

try {
    // Düzeltilecek dosyaları kontrol et
    $checkStmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM file_uploads 
        WHERE is_cancelled = 1 
        AND status != 'cancelled'
    ");
    
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    $affectedCount = $result['count'];
    
    if ($affectedCount == 0) {
        echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #e8f5e9;'>";
        echo "✅ Tüm iptal edilmiş dosyalar zaten doğru status'a sahip. Düzeltme gerekmedi.";
        echo "</div>";
    } else {
        echo "<div style='color: orange; padding: 10px; border: 1px solid orange; background: #fff3e0;'>";
        echo "⚠️ <strong>{$affectedCount} adet</strong> iptal edilmiş dosyanın status'u güncellenmemiş.";
        echo "</div>";
        
        // Düzeltme işlemini başlat
        $updateStmt = $pdo->prepare("
            UPDATE file_uploads 
            SET status = 'cancelled' 
            WHERE is_cancelled = 1 
            AND status != 'cancelled'
        ");
        
        $updateStmt->execute();
        
        $updatedCount = $updateStmt->rowCount();
        
        echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #e8f5e9; margin-top: 10px;'>";
        echo "✅ <strong>{$updatedCount} adet</strong> dosyanın status'u 'cancelled' olarak güncellendi.";
        echo "</div>";
        
        // Düzeltilen dosyaları listele
        echo "<h3>Düzeltilen Dosyalar:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f5f5f5;'>";
        echo "<th>ID</th><th>Dosya Adı</th><th>Plaka</th><th>Eski Status</th><th>Yeni Status</th><th>İptal Tarihi</th>";
        echo "</tr>";
        
        $listStmt = $pdo->query("
            SELECT id, original_name, plate, status, cancelled_at 
            FROM file_uploads 
            WHERE is_cancelled = 1 
            AND status = 'cancelled'
            ORDER BY cancelled_at DESC
            LIMIT 50
        ");
        
        $files = $listStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($files as $file) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars(substr($file['id'], 0, 8)) . "...</td>";
            echo "<td>" . htmlspecialchars($file['original_name']) . "</td>";
            echo "<td>" . htmlspecialchars($file['plate'] ?? '-') . "</td>";
            echo "<td style='color: red;'>processing/completed</td>";
            echo "<td style='color: green; font-weight: bold;'>cancelled</td>";
            echo "<td>" . htmlspecialchars($file['cancelled_at'] ?? '-') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        if (count($files) >= 50) {
            echo "<p><em>Not: Sadece ilk 50 dosya gösteriliyor. Toplam {$updatedCount} dosya güncellendi.</em></p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Migration Özeti:</h3>";
    echo "<ul>";
    echo "<li>Kontrol Edilen Tablo: <strong>file_uploads</strong></li>";
    echo "<li>Kontrol Koşulu: <strong>is_cancelled = 1 AND status != 'cancelled'</strong></li>";
    echo "<li>Güncelleme: <strong>status = 'cancelled'</strong></li>";
    echo "<li>Bulunan Dosya: <strong>{$affectedCount} adet</strong></li>";
    echo "<li>Güncellenen Dosya: <strong>" . ($updatedCount ?? 0) . " adet</strong></li>";
    echo "</ul>";
    
    echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #e8f5e9; margin-top: 20px;'>";
    echo "✅ <strong>Migration başarıyla tamamlandı!</strong>";
    echo "</div>";
    
    echo "<p><a href='../admin/uploads.php?status=cancelled'>İptal Edilmiş Dosyaları Görüntüle</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; background: #ffebee;'>";
    echo "❌ <strong>Hata:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    error_log('Migration error: ' . $e->getMessage());
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
    }
    h2 {
        color: #1976d2;
    }
    table {
        font-size: 14px;
    }
    th {
        text-align: left;
    }
</style>
