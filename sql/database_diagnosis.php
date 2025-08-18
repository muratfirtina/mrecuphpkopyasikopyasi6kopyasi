<?php
/**
 * Mr ECU - Veritabanı Yapı Kontrolü ve Tanı
 * İptal sistemi için gerekli tabloları ve sütunları kontrol eder
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    die('Bu sayfaya erişim izniniz yok.');
}

echo "<!DOCTYPE html>\n<html><head><title>Veritabanı Tanı</title></head><body>";
echo "<h1>Veritabanı Yapı Kontrolü ve Tanı</h1>";

try {
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>1. Mevcut Tablolar</h2>";
    
    // Tüm tabloları listele
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>Toplam Tablo Sayısı:</strong> " . count($allTables) . "</p>";
    echo "<details><summary>Tüm Tabloları Göster</summary><ul>";
    foreach ($allTables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul></details>";
    echo "</div>";
    
    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>2. İptal Sistemi İçin Gerekli Tablolar</h2>";
    
    $requiredTables = [
        'file_uploads' => 'Ana dosyalar',
        'file_responses' => 'Yanıt dosyaları', 
        'revision_files' => 'Revizyon dosyaları',
        'additional_files' => 'Ek dosyalar',
        'file_cancellations' => 'İptal talepleri'
    ];
    
    $existingRequired = [];
    foreach ($requiredTables as $table => $description) {
        if (in_array($table, $allTables)) {
            echo "<p style='color: green;'>✓ $description ($table): Mevcut</p>";
            $existingRequired[$table] = $description;
        } else {
            echo "<p style='color: red;'>✗ $description ($table): Eksik</p>";
        }
    }
    echo "</div>";
    
    echo "<div style='background: #f3e5f5; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>3. Mevcut Tablo Yapıları</h2>";
    
    foreach ($existingRequired as $table => $description) {
        echo "<h3>$description ($table):</h3>";
        
        try {
            // Tablo yapısını göster
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background: #e9ecef;'><th>Sütun</th><th>Tip</th><th>Null</th><th>Anahtar</th><th>Varsayılan</th><th>Extra</th></tr>";
            
            $hasIsCancelled = false;
            $hasCancelledAt = false;
            $hasCancelledBy = false;
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td><strong>" . $column['Field'] . "</strong></td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
                echo "<td>" . $column['Extra'] . "</td>";
                echo "</tr>";
                
                // İptal sütunlarını kontrol et
                if ($column['Field'] === 'is_cancelled') $hasIsCancelled = true;
                if ($column['Field'] === 'cancelled_at') $hasCancelledAt = true;
                if ($column['Field'] === 'cancelled_by') $hasCancelledBy = true;
            }
            echo "</table>";
            
            // İptal sütunları durumu
            echo "<div style='margin: 10px 0; padding: 10px; background: #e8f5e8; border-left: 4px solid #4caf50;'>";
            echo "<strong>İptal Sistemi Sütunları:</strong><br>";
            echo ($hasIsCancelled ? "✓" : "✗") . " is_cancelled<br>";
            echo ($hasCancelledAt ? "✓" : "✗") . " cancelled_at<br>";
            echo ($hasCancelledBy ? "✓" : "✗") . " cancelled_by<br>";
            echo "</div>";
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>4. İndeks Kontrolü</h2>";
    
    foreach ($existingRequired as $table => $description) {
        if ($table === 'file_cancellations') continue; // Bu tabloda is_cancelled yok
        
        echo "<h4>$description ($table):</h4>";
        
        try {
            $stmt = $pdo->query("SHOW INDEX FROM $table");
            $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $hasIsCancelledIndex = false;
            foreach ($indexes as $index) {
                if ($index['Key_name'] === 'idx_is_cancelled') {
                    $hasIsCancelledIndex = true;
                    break;
                }
            }
            
            if ($hasIsCancelledIndex) {
                echo "<p style='color: green; margin-left: 20px;'>✓ idx_is_cancelled indeksi mevcut</p>";
            } else {
                echo "<p style='color: orange; margin-left: 20px;'>⚠ idx_is_cancelled indeksi eksik</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color: red; margin-left: 20px;'>Hata: " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>5. Öneri ve Çözümler</h2>";
    
    $missingTables = array_diff(array_keys($requiredTables), array_keys($existingRequired));
    
    if (!empty($missingTables)) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>❌ Eksik Tablolar:</h4>";
        foreach ($missingTables as $table) {
            echo "<p>• $table - " . $requiredTables[$table] . "</p>";
        }
        echo "<p><strong>Çözüm:</strong> Önce iptal sistemi kurulumunu yapın:</p>";
        echo "<a href='../sql/install_cancellation_system.php' style='background: #dc3545; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>İptal Sistemi Kur</a>";
        echo "</div>";
    }
    
    // Eksik sütunları kontrol et
    $needsMigration = false;
    foreach ($existingRequired as $table => $description) {
        if ($table === 'file_cancellations') continue;
        
        $stmt = $pdo->prepare("SHOW COLUMNS FROM $table LIKE 'is_cancelled'");
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            $needsMigration = true;
            break;
        }
    }
    
    if ($needsMigration) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>⚠ Eksik Sütunlar Var:</h4>";
        echo "<p>İptal sistemi için gerekli sütunlar eksik.</p>";
        echo "<p><strong>Çözüm:</strong> Güvenli migration'u çalıştırın:</p>";
        echo "<a href='install_cancellation_columns_safe.php' style='background: #ffc107; color: black; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Güvenli Migration Çalıştır</a>";
        echo "</div>";
    } else {
        echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>✅ Sistem Hazır:</h4>";
        echo "<p>Tüm gerekli tablolar ve sütunlar mevcut!</p>";
        echo "<a href='../test_cancellation_features.php' style='background: #17a2b8; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>Sistemi Test Et</a>";
        echo "</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2 style='color: red;'>Tanı Hatası:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<br><a href='../admin/dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Admin Paneline Dön</a>";
echo "</body></html>";
?>
