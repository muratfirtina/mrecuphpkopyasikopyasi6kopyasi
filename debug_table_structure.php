<?php
/**
 * Debug: Revisions tablosu yapısını kontrol et
 */
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Debug: Revisions Tablo Yapısı</h2>";

try {
    // 1. Revisions tablosunun sütunlarını listele
    echo "<h3>1. Revisions Tablosu Sütunları:</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM revisions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Sütun Adı</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $col['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 2. Örnek revisions verilerini göster
    echo "<h3>2. Örnek Revisions Verileri:</h3>";
    $stmt = $pdo->query("SELECT * FROM revisions LIMIT 5");
    $revisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($revisions) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        $first = true;
        foreach ($revisions as $rev) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($rev) as $key) {
                    echo "<th>" . $key . "</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($rev as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Revisions tablosunda veri bulunamadı.</p>";
    }
    
    // 3. İşleme alınan revize taleplerini kontrol et
    echo "<h3>3. İşleme Alınan Revize Talepleri (Detaylı):</h3>";
    $stmt = $pdo->prepare("
        SELECT r.*, fu.original_name, fu.plate
        FROM revisions r
        LEFT JOIN file_uploads fu ON r.upload_id = fu.id
        WHERE r.status = 'in_progress'
        ORDER BY r.requested_at DESC
    ");
    $stmt->execute();
    $inProgressRevisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($inProgressRevisions) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        $first = true;
        foreach ($inProgressRevisions as $rev) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($rev) as $key) {
                    echo "<th>" . $key . "</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($rev as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>İşleme alınan revize talebi bulunamadı.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
}
?>
