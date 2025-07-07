<?php
/**
 * Quick Database Check - Gerçek upload'ları kontrol et
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Gerekli sınıfları ve fonksiyonları include et
if (!function_exists('isValidUUID')) {
    require_once '../includes/functions.php';
}
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

echo "<h2>📊 Database Upload Check</h2>";

try {
    // Tüm upload'ları listele
    $stmt = $pdo->query("SELECT id, original_name, filename, file_size, status, upload_date FROM file_uploads ORDER BY upload_date DESC LIMIT 10");
    $uploads = $stmt->fetchAll();
    
    echo "<h3>✅ Veritabanında Bulunan Dosyalar:</h3>";
    
    if (empty($uploads)) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "❌ <strong>Veritabanında hiç dosya yok!</strong><br>";
        echo "Bu durumda test etmek için önce bir dosya yüklemeniz gerekiyor.";
        echo "</div>";
        
        echo "<h3>🔧 Çözüm:</h3>";
        echo "<ol>";
        echo "<li>Kullanıcı panelinden bir dosya yükleyin</li>";
        echo "<li>Ya da test için sample data ekleyin</li>";
        echo "</ol>";
        
        echo "<a href='../index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ana Sayfaya Git</a>";
        
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th style='padding: 10px;'>ID</th>";
        echo "<th style='padding: 10px;'>Dosya Adı</th>";
        echo "<th style='padding: 10px;'>Filename</th>";
        echo "<th style='padding: 10px;'>Boyut</th>";
        echo "<th style='padding: 10px;'>Durum</th>";
        echo "<th style='padding: 10px;'>Test Links</th>";
        echo "</tr>";
        
        foreach ($uploads as $upload) {
            echo "<tr>";
            echo "<td style='padding: 5px; font-size: 11px;'>" . $upload['id'] . "</td>";
            echo "<td style='padding: 5px;'>" . htmlspecialchars($upload['original_name']) . "</td>";
            echo "<td style='padding: 5px;'>" . htmlspecialchars($upload['filename']) . "</td>";
            echo "<td style='padding: 5px;'>" . formatFileSize($upload['file_size'] ?? 0) . "</td>";
            echo "<td style='padding: 5px;'>" . $upload['status'] . "</td>";
            echo "<td style='padding: 5px;'>";
            echo "<a href='download-test.php?type=original&id=" . $upload['id'] . "' style='color: #007bff; margin-right: 10px;'>Download</a>";
            echo "<a href='detail-test.php?id=" . $upload['id'] . "' style='color: #28a745;'>Detail</a>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<h3>📁 Fiziksel Dosya Kontrolü:</h3>";
        
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/';
        
        if (is_dir($uploadDir)) {
            $files = array_diff(scandir($uploadDir), ['.', '..']);
            echo "Upload dizininde " . count($files) . " dosya bulundu:<br>";
            
            echo "<ul>";
            foreach ($files as $file) {
                $fullPath = $uploadDir . $file;
                $size = file_exists($fullPath) ? filesize($fullPath) : 0;
                echo "<li>$file (" . formatFileSize($size) . ")</li>";
            }
            echo "</ul>";
            
            // Database ile fiziksel dosyaları eşleştir
            echo "<h3>🔍 Database-File Mapping:</h3>";
            
            foreach ($uploads as $upload) {
                $filename = $upload['filename'];
                $fullPath = $uploadDir . $filename;
                $exists = file_exists($fullPath);
                
                echo "<div style='margin: 10px 0; padding: 10px; background: " . ($exists ? "#d4edda" : "#f8d7da") . "; border-radius: 5px;'>";
                echo "<strong>" . htmlspecialchars($upload['original_name']) . "</strong><br>";
                echo "Database filename: " . htmlspecialchars($filename) . "<br>";
                echo "File exists: " . ($exists ? "✅ YES" : "❌ NO") . "<br>";
                
                if ($exists) {
                    echo "Gerçek test linkleri:<br>";
                    echo "<a href='download.php?type=original&id=" . $upload['id'] . "' target='_blank' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; margin-right: 5px;'>Real Download</a>";
                    echo "<a href='file-detail.php?id=" . $upload['id'] . "' target='_blank' style='background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Real Detail</a>";
                }
                
                echo "</div>";
            }
            
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
            echo "❌ Upload dizini bulunamadı: $uploadDir";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ Database hatası: " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='uploads.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Uploads Ana Sayfa</a>";
echo "<a href='test-summary.php' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Test Summary</a>";
echo "</div>";
?>
