<?php
/**
 * Find Available Upload IDs
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Available Upload IDs Check</h1>";

try {
    // Mevcut file_uploads kayıtlarını listele
    echo "<h2>1. Available File Uploads</h2>";
    
    $stmt = $pdo->query("
        SELECT fu.id, fu.original_name, fu.status, fu.upload_date, 
               u.username, u.first_name, u.last_name,
               b.name as brand_name, m.name as model_name
        FROM file_uploads fu
        LEFT JOIN users u ON fu.user_id = u.id
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        ORDER BY fu.upload_date DESC
        LIMIT 20
    ");
    
    $uploads = $stmt->fetchAll();
    
    if (empty($uploads)) {
        echo "❌ Hiç dosya upload kaydı bulunamadı!<br><br>";
        
        echo "<h3>Test Upload Kaydı Oluşturuluyor...</h3>";
        
        // Test user kontrol
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'user' LIMIT 1");
        $testUser = $stmt->fetch();
        
        if (!$testUser) {
            echo "❌ Test user bulunamadı<br>";
        } else {
            // Test brand/model kontrol
            $stmt = $pdo->query("SELECT id FROM brands LIMIT 1");
            $testBrand = $stmt->fetch();
            
            $stmt = $pdo->query("SELECT id FROM models LIMIT 1");
            $testModel = $stmt->fetch();
            
            // Test upload kaydı oluştur
            $testUploadId = generateUUID();
            $stmt = $pdo->prepare("
                INSERT INTO file_uploads (
                    id, user_id, brand_id, model_id, original_name, filename, 
                    file_size, status, upload_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'processing', NOW())
            ");
            
            $result = $stmt->execute([
                $testUploadId,
                $testUser['id'],
                $testBrand['id'] ?? null,
                $testModel['id'] ?? null,
                'Test_Upload_File.bin',
                'test_' . time() . '.bin',
                1024000,
            ]);
            
            if ($result) {
                echo "✅ Test upload kaydı oluşturuldu!<br>";
                echo "<strong>Test Upload ID:</strong> " . $testUploadId . "<br>";
                
                // Bu ID ile test linklerini göster
                echo "<h3>Test Links:</h3>";
                echo "<p><a href='admin/file-detail.php?id=" . $testUploadId . "' target='_blank'>File Detail - Test Upload</a></p>";
                echo "<p><a href='debug-file-upload.php' target='_blank'>Debug Upload Test</a> (Bu ID'yi kullanın: <code>" . $testUploadId . "</code>)</p>";
                
            } else {
                echo "❌ Test upload kaydı oluşturulamadı<br>";
            }
        }
        
    } else {
        echo "✅ " . count($uploads) . " dosya upload kaydı bulundu:<br><br>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Dosya Adı</th><th>Kullanıcı</th><th>Durum</th><th>Tarih</th><th>Test Linkleri</th></tr>";
        
        foreach ($uploads as $upload) {
            echo "<tr>";
            echo "<td style='font-family: monospace; font-size: 0.8em;'>" . $upload['id'] . "</td>";
            echo "<td>" . htmlspecialchars($upload['original_name']) . "</td>";
            echo "<td>" . htmlspecialchars($upload['first_name'] . ' ' . $upload['last_name']) . " (@" . htmlspecialchars($upload['username']) . ")</td>";
            echo "<td><span style='padding: 2px 6px; background: " . 
                 ($upload['status'] === 'completed' ? '#d4edda' : 
                  ($upload['status'] === 'processing' ? '#fff3cd' : 
                   ($upload['status'] === 'pending' ? '#e2e3e5' : '#f8d7da'))) . 
                 ";'>" . $upload['status'] . "</span></td>";
            echo "<td>" . date('d.m.Y H:i', strtotime($upload['upload_date'])) . "</td>";
            echo "<td>";
            echo "<a href='admin/file-detail.php?id=" . $upload['id'] . "' target='_blank' style='margin-right: 10px;'>File Detail</a>";
            echo "<a href='debug-file-upload.php?upload_id=" . $upload['id'] . "' target='_blank'>Debug Test</a>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // İlk dosyayı highlight et
        $firstUpload = $uploads[0];
        echo "<h3>🎯 Önerilen Test ID:</h3>";
        echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Upload ID:</strong> <code style='background: #fff; padding: 2px 5px;'>" . $firstUpload['id'] . "</code><br>";
        echo "<strong>Dosya:</strong> " . htmlspecialchars($firstUpload['original_name']) . "<br>";
        echo "<strong>Durum:</strong> " . $firstUpload['status'] . "<br>";
        echo "<br>";
        echo "<strong>Test Linkleri:</strong><br>";
        echo "📁 <a href='admin/file-detail.php?id=" . $firstUpload['id'] . "' target='_blank'>File Detail Sayfası</a><br>";
        echo "🧪 <a href='debug-file-upload.php' target='_blank'>Upload Debug Test</a> (Form'da bu ID'yi kullanın)<br>";
        echo "</div>";
    }
    
    // Response files kontrol
    echo "<h2>2. Existing Response Files</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) FROM file_responses");
    $responseCount = $stmt->fetchColumn();
    echo "Mevcut yanıt dosyası sayısı: " . $responseCount . "<br>";
    
    if ($responseCount > 0) {
        $stmt = $pdo->query("
            SELECT fr.id, fr.original_name, fr.upload_date, fu.original_name as parent_file
            FROM file_responses fr
            LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
            ORDER BY fr.upload_date DESC
            LIMIT 5
        ");
        $responses = $stmt->fetchAll();
        
        echo "<h3>Son 5 Yanıt Dosyası:</h3>";
        foreach ($responses as $response) {
            echo "- " . htmlspecialchars($response['original_name']) . " (Ana dosya: " . htmlspecialchars($response['parent_file']) . ")<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2, h3 { color: #333; }
    table { margin: 15px 0; }
    th, td { padding: 8px 12px; text-align: left; }
    th { background-color: #f0f0f0; }
    a { color: #007bff; text-decoration: none; }
    a:hover { text-decoration: underline; }
    code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
</style>
