<?php
/**
 * Database'deki tüm tabloları listeleyen script
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Database'deki Tüm Tablolar</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    .error { color: red; }
    .success { color: green; }
    .warning { color: orange; }
</style>";

try {
    // Database adını al
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $dbName = $stmt->fetch()['db_name'];
    echo "<p><strong>Database:</strong> $dbName</p>";
    
    // Tüm tabloları listele
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Mevcut Tablolar (" . count($tables) . " adet):</h3>";
    echo "<table>";
    echo "<tr><th>Tablo Adı</th><th>Kayıt Sayısı</th><th>Durum</th></tr>";
    
    if (empty($tables)) {
        echo "<tr><td colspan='3' class='error'>Hiç tablo bulunamadı!</td></tr>";
    } else {
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `$table`");
                $stmt->execute();
                $count = $stmt->fetch()['count'];
                
                $status = '<span class="success">✓ Erişilebilir</span>';
                if ($count == 0) {
                    $status .= ' <span class="warning">(Boş)</span>';
                }
                
                echo "<tr>";
                echo "<td><strong>$table</strong></td>";
                echo "<td>$count</td>";
                echo "<td>$status</td>";
                echo "</tr>";
                
            } catch (Exception $e) {
                echo "<tr>";
                echo "<td><strong>$table</strong></td>";
                echo "<td>-</td>";
                echo "<td><span class='error'>✗ Hata: " . $e->getMessage() . "</span></td>";
                echo "</tr>";
            }
        }
    }
    echo "</table>";
    
    // Chip tuning ile ilgili tabloları kontrol et
    $chipTuningTables = ['brands', 'models', 'series', 'engines', 'stages'];
    echo "<h3>Chip Tuning Tabloları Kontrolü:</h3>";
    echo "<table>";
    echo "<tr><th>Tablo</th><th>Durum</th><th>Kayıt Sayısı</th></tr>";
    
    foreach ($chipTuningTables as $table) {
        try {
            if (in_array($table, $tables)) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `$table`");
                $stmt->execute();
                $count = $stmt->fetch()['count'];
                
                if ($count > 0) {
                    echo "<tr><td><strong>$table</strong></td><td class='success'>✓ Var</td><td>$count</td></tr>";
                } else {
                    echo "<tr><td><strong>$table</strong></td><td class='warning'>⚠ Boş</td><td>0</td></tr>";
                }
            } else {
                echo "<tr><td><strong>$table</strong></td><td class='error'>✗ Yok</td><td>-</td></tr>";
            }
        } catch (Exception $e) {
            echo "<tr><td><strong>$table</strong></td><td class='error'>✗ Hata</td><td>" . $e->getMessage() . "</td></tr>";
        }
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p class='error'>Genel Hata: " . $e->getMessage() . "</p>";
}

?>
