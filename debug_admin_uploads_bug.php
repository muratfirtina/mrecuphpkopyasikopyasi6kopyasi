<?php
/**
 * Admin Uploads Debug - Foreach Reference Bug Tespiti
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Admin Uploads - Foreach Reference Bug Debug</h2>";

// Admin uploads sayfasındaki sorguyu simüle et
try {
    $whereClause = "WHERE 1=1";
    $params = [];
    $limit = 10;
    $offset = 0;
    $sortBy = 'upload_date';
    $sortOrder = 'DESC';
    
    // Admin uploads sorgusu
    $query = "
        SELECT u.*, 
               users.username, users.email, users.first_name, users.last_name,
               b.name as brand_name,
               m.name as model_name,
               s.name as series_name,
               e.name as engine_name
        FROM file_uploads u
        LEFT JOIN users ON u.user_id = users.id
        LEFT JOIN brands b ON u.brand_id = b.id
        LEFT JOIN models m ON u.model_id = m.id
        LEFT JOIN series s ON u.series_id = s.id
        LEFT JOIN engines e ON u.engine_id = e.id
        $whereClause 
        ORDER BY u.$sortBy $sortOrder 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $uploads = $stmt->fetchAll();
    
    echo "<h3>Bulunan Upload Sayısı: " . count($uploads) . "</h3>";
    
    if (count($uploads) > 0) {
        echo "<h3>HATALI YÖNTEM (Foreach Referans - Sorunlu):</h3>";
        
        // Hatalı yöntem - foreach ile referans
        $uploadsWithRef = $uploads; // Kopyasını al
        foreach ($uploadsWithRef as &$upload) {
            // Ek işlemler simülasyonu
            $upload['processed'] = true;
            $upload['debug_info'] = 'Processed at ' . date('H:i:s');
        }
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Index</th><th>Upload ID</th><th>Username</th><th>Original Name</th><th>Debug Info</th></tr>";
        foreach ($uploadsWithRef as $index => $upload) {
            echo "<tr>";
            echo "<td>$index</td>";
            echo "<td>" . substr($upload['id'] ?? 'NO_ID', 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($upload['username'] ?? 'NO_USER') . "</td>";
            echo "<td>" . htmlspecialchars($upload['original_name'] ?? 'NO_NAME') . "</td>";
            echo "<td>" . htmlspecialchars($upload['debug_info'] ?? 'NO_DEBUG') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>DOĞRU YÖNTEM (For Loop - Düzeltilmiş):</h3>";
        
        // Doğru yöntem - for loop
        $uploadsWithFor = $uploads; // Kopyasını al
        for ($i = 0; $i < count($uploadsWithFor); $i++) {
            // Ek işlemler simülasyonu
            $uploadsWithFor[$i]['processed'] = true;
            $uploadsWithFor[$i]['debug_info'] = 'Processed at ' . date('H:i:s') . ' - Index: ' . $i;
        }
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Index</th><th>Upload ID</th><th>Username</th><th>Original Name</th><th>Debug Info</th></tr>";
        foreach ($uploadsWithFor as $index => $upload) {
            echo "<tr>";
            echo "<td>$index</td>";
            echo "<td>" . substr($upload['id'] ?? 'NO_ID', 0, 8) . "...</td>";
            echo "<td>" . htmlspecialchars($upload['username'] ?? 'NO_USER') . "</td>";
            echo "<td>" . htmlspecialchars($upload['original_name'] ?? 'NO_NAME') . "</td>";
            echo "<td>" . htmlspecialchars($upload['debug_info'] ?? 'NO_DEBUG') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Sonuç Karşılaştırması:</h3>";
        echo "<p><strong>Foreach Referans:</strong> Muhtemelen son işlenen değerler tüm kayıtlarda tekrarlanır</p>";
        echo "<p><strong>For Loop:</strong> Her kayıt kendi doğru değerlerine sahiptir</p>";
        
    } else {
        echo "<p>Upload bulunamadı</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h3>Admin/uploads.php Düzeltme Önerisi:</h3>";
echo "<p>1. Dosyada <code>foreach (\$uploads as &\$upload)</code> satırını bulun</p>";
echo "<p>2. Bunu <code>for (\$i = 0; \$i < count(\$uploads); \$i++)</code> ile değiştirin</p>";
echo "<p>3. <code>\$upload</code> referanslarını <code>\$uploads[\$i]</code> ile değiştirin</p>";
?>
