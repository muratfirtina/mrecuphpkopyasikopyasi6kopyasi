<?php
/**
 * Marka Çift Kayıt Debug Sayfası
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Marka Çift Kayıt Analizi</h1>";

try {
    // Çift kayıtları bul
    $stmt = $pdo->query("
        SELECT name, COUNT(*) as count, GROUP_CONCAT(id) as ids
        FROM brands 
        GROUP BY name 
        HAVING COUNT(*) > 1
        ORDER BY count DESC, name
    ");
    $duplicates = $stmt->fetchAll();
    
    echo "<h2>Çift Kayıtlar:</h2>";
    
    if (empty($duplicates)) {
        echo "<p style='color: green;'>✅ Çift kayıt bulunamadı!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Marka Adı</th><th>Kayıt Sayısı</th><th>ID'ler</th><th>Detay</th></tr>";
        
        foreach ($duplicates as $dup) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($dup['name']) . "</strong></td>";
            echo "<td style='text-align: center;'>" . $dup['count'] . "</td>";
            echo "<td>" . $dup['ids'] . "</td>";
            
            // Her ID için detay göster
            $ids = explode(',', $dup['ids']);
            echo "<td>";
            foreach ($ids as $id) {
                $stmt = $pdo->prepare("
                    SELECT b.*, COUNT(m.id) as model_count 
                    FROM brands b 
                    LEFT JOIN models m ON b.id = m.brand_id 
                    WHERE b.id = ?
                ");
                $stmt->execute([$id]);
                $brand = $stmt->fetch();
                
                echo "<div style='margin-bottom: 5px; padding: 5px; border: 1px solid #ccc;'>";
                echo "<strong>ID: {$brand['id']}</strong><br>";
                echo "Durum: {$brand['status']}<br>";
                echo "Model Sayısı: {$brand['model_count']}<br>";
                echo "Oluşturma: " . ($brand['created_at'] ?? 'Bilinmiyor') . "<br>";
                echo "</div>";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Tüm markaları listele
    echo "<h2>Tüm Markalar:</h2>";
    $stmt = $pdo->query("
        SELECT b.*, COUNT(m.id) as model_count 
        FROM brands b 
        LEFT JOIN models m ON b.id = m.brand_id 
        GROUP BY b.id 
        ORDER BY b.name, b.id
    ");
    $allBrands = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Marka Adı</th><th>Durum</th><th>Model Sayısı</th><th>Oluşturma</th></tr>";
    
    foreach ($allBrands as $brand) {
        $isDuplicate = false;
        foreach ($duplicates as $dup) {
            if (in_array($brand['id'], explode(',', $dup['ids']))) {
                $isDuplicate = true;
                break;
            }
        }
        
        $rowColor = $isDuplicate ? 'background-color: #ffcccc;' : '';
        
        echo "<tr style='$rowColor'>";
        echo "<td>{$brand['id']}</td>";
        echo "<td>" . htmlspecialchars($brand['name']) . "</td>";
        echo "<td>{$brand['status']}</td>";
        echo "<td>{$brand['model_count']}</td>";
        echo "<td>" . ($brand['created_at'] ?? 'Bilinmiyor') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}

echo "<br><hr><br>";
echo "<a href='admin/brands.php'>📁 Brands Yönetimi'ne Git</a><br>";
echo "<a href='brands-clean.php'>🧹 Çift Kayıtları Temizle</a>";
?>
