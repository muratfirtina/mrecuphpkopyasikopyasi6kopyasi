<?php
/**
 * Veritabanı tablo kontrolü ve debug
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Veritabanı Tablo Kontrolü</h1>";

try {
    // Tabloları listele
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Mevcut Tablolar:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Her tablodaki kayıt sayısını kontrol et
    echo "<h2>Tablo Kayıt Sayıları:</h2>";
    echo "<table border='1' style='border-collapse:collapse;'>";
    echo "<tr><th>Tablo</th><th>Kayıt Sayısı</th><th>Örnek Veri</th></tr>";
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            
            // İlk kayıtı al
            $stmt = $pdo->query("SELECT * FROM $table LIMIT 1");
            $sample = $stmt->fetch();
            
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>$count</td>";
            echo "<td>" . ($sample ? "✅ Veri var" : "❌ Boş") . "</td>";
            echo "</tr>";
        } catch (Exception $e) {
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td colspan='2'>HATA: " . $e->getMessage() . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    // User sınıfını test et
    echo "<h2>User Sınıfı Test:</h2>";
    try {
        require_once '../includes/User.php';
        $user = new User($pdo);
        $userCount = $user->getUserCount();
        echo "<p>getUserCount(): $userCount (" . gettype($userCount) . ")</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>User sınıfı hatası: " . $e->getMessage() . "</p>";
    }
    
    // FileManager sınıfını test et
    echo "<h2>FileManager Sınıfı Test:</h2>";
    try {
        require_once '../includes/FileManager.php';
        $fileManager = new FileManager($pdo);
        $fileStats = $fileManager->getFileStats();
        echo "<p>getFileStats():</p>";
        echo "<pre>" . print_r($fileStats, true) . "</pre>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>FileManager sınıfı hatası: " . $e->getMessage() . "</p>";
    }
    
    // Krediler testi
    echo "<h2>Kredi Sorgusu Test:</h2>";
    try {
        $stmt = $pdo->query("SELECT SUM(credits) as total_credits FROM users WHERE role = 'user'");
        $result = $stmt->fetch();
        $totalCredits = $result['total_credits'] ?? 0;
        echo "<p>Toplam kredi: $totalCredits (" . gettype($totalCredits) . ")</p>";
        echo "<p>number_format test: " . number_format((float)$totalCredits, 2) . "</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Kredi sorgusu hatası: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Genel hata: " . $e->getMessage() . "</p>";
}

echo "<br><a href='../config/install.php'>Kurulum Sayfasına Git</a><br>";
echo "<a href='index.php'>Admin Paneline Geri Dön</a>";
?>
