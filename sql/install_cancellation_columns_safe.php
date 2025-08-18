<?php
/**
 * Mr ECU - İptal Sistemi Sütunları Ekleme Migration (Güvenli Versiyon)
 * Bu dosya iptal sistemi için gerekli sütunları veritabanına adım adım ekler
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    die('Bu sayfaya erişim izniniz yok.');
}

echo "<!DOCTYPE html>\n<html><head><title>İptal Sistemi Migration - Güvenli</title></head><body>";
echo "<h1>İptal Sistemi Sütunları Ekleniyor (Güvenli Versiyon)...</h1>";

try {
    $successCount = 0;
    $errorCount = 0;
    $skippedCount = 0;
    
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>1. Mevcut Tablo Yapıları Kontrol Ediliyor:</h3>";
    
    // Önce hangi tabloların mevcut olduğunu kontrol edelim
    $tables = [
        'file_uploads' => 'Ana dosyalar',
        'file_responses' => 'Yanıt dosyaları', 
        'revision_files' => 'Revizyon dosyaları',
        'additional_files' => 'Ek dosyalar'
    ];
    
    $existingTables = [];
    foreach ($tables as $table => $description) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>✓ $description ($table): Tablo mevcut</p>";
                $existingTables[$table] = $description;
                
                // Mevcut sütunları kontrol et
                $colStmt = $pdo->query("SHOW COLUMNS FROM $table");
                $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN);
                echo "<p style='color: #666; margin-left: 20px;'>Mevcut sütunlar: " . implode(', ', array_slice($columns, 0, 5)) . "...</p>";
            } else {
                echo "<p style='color: orange;'>⚠ $description ($table): Tablo bulunamadı</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ $description ($table): Kontrol hatası - " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
    
    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>2. Sütunlar Ekleniyor:</h3>";
    
    // Her tablo için sütunları tek tek ekle
    $columnsToAdd = [
        'is_cancelled' => 'TINYINT(1) DEFAULT 0 COMMENT \'Dosya iptal edildi mi?\'',
        'cancelled_at' => 'TIMESTAMP NULL COMMENT \'İptal tarihi\'', 
        'cancelled_by' => 'VARCHAR(36) NULL COMMENT \'İptal eden admin ID\''
    ];
    
    foreach ($existingTables as $table => $description) {
        echo "<h4>$description ($table):</h4>";
        
        foreach ($columnsToAdd as $columnName => $columnDefinition) {
            try {
                // Önce sütunun var olup olmadığını kontrol et
                $checkStmt = $pdo->prepare("SHOW COLUMNS FROM $table LIKE ?");
                $checkStmt->execute([$columnName]);
                
                if ($checkStmt->rowCount() > 0) {
                    echo "<p style='color: orange; margin-left: 20px;'>⚠ $columnName: Zaten mevcut</p>";
                    $skippedCount++;
                } else {
                    // Sütunu ekle
                    $sql = "ALTER TABLE $table ADD COLUMN $columnName $columnDefinition";
                    $pdo->exec($sql);
                    echo "<p style='color: green; margin-left: 20px;'>✓ $columnName: Başarıyla eklendi</p>";
                    $successCount++;
                }
            } catch (PDOException $e) {
                echo "<p style='color: red; margin-left: 20px;'>✗ $columnName: Hata - " . $e->getMessage() . "</p>";
                $errorCount++;
            }
        }
    }
    echo "</div>";
    
    echo "<div style='background: #f3e5f5; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>3. İndeksler Ekleniyor:</h3>";
    
    // Sütunlar eklendikten sonra indeksleri ekle
    foreach ($existingTables as $table => $description) {
        try {
            // Önce indeksin var olup olmadığını kontrol et
            $indexCheckStmt = $pdo->query("SHOW INDEX FROM $table WHERE Key_name = 'idx_is_cancelled'");
            
            if ($indexCheckStmt->rowCount() > 0) {
                echo "<p style='color: orange;'>⚠ $description ($table): İndeks zaten mevcut</p>";
                $skippedCount++;
            } else {
                // is_cancelled sütunu var mı kontrol et
                $colCheckStmt = $pdo->prepare("SHOW COLUMNS FROM $table LIKE 'is_cancelled'");
                $colCheckStmt->execute();
                
                if ($colCheckStmt->rowCount() > 0) {
                    $sql = "ALTER TABLE $table ADD INDEX idx_is_cancelled (is_cancelled)";
                    $pdo->exec($sql);
                    echo "<p style='color: green;'>✓ $description ($table): İndeks eklendi</p>";
                    $successCount++;
                } else {
                    echo "<p style='color: red;'>✗ $description ($table): is_cancelled sütunu bulunamadı, indeks eklenemedi</p>";
                    $errorCount++;
                }
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ $description ($table) indeks: " . $e->getMessage() . "</p>";
            $errorCount++;
        }
    }
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Migration Özeti:</h3>";
    echo "<p><strong>Başarılı işlemler:</strong> $successCount</p>";
    echo "<p><strong>Hatalı işlemler:</strong> $errorCount</p>";
    echo "<p><strong>Atlanan işlemler:</strong> $skippedCount</p>";
    
    if ($errorCount == 0) {
        echo "<p style='color: green; font-weight: bold;'>🎉 Migration başarıyla tamamlandı!</p>";
    } elseif ($successCount > 0) {
        echo "<p style='color: orange; font-weight: bold;'>⚠ Migration kısmen tamamlandı.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Migration başarısız oldu.</p>";
    }
    echo "</div>";
    
    // Son kontrol
    echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>4. Final Kontrol:</h3>";
    
    $allReady = true;
    foreach ($existingTables as $table => $description) {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM $table LIKE 'is_cancelled'");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result) {
                echo "<p style='color: green;'>✓ $description ($table): is_cancelled sütunu hazır</p>";
            } else {
                echo "<p style='color: red;'>✗ $description ($table): is_cancelled sütunu eksik</p>";
                $allReady = false;
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ $description ($table): Kontrol hatası - " . $e->getMessage() . "</p>";
            $allReady = false;
        }
    }
    
    if ($allReady) {
        echo "<p style='color: green; font-weight: bold; font-size: 18px;'>🚀 İptal sistemi kullanıma hazır!</p>";
        echo "<p><a href='../test_cancellation_features.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Sistemi Test Et</a></p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>⚠ Bazı tablolarda sorunlar var. Manuel kontrol gerekli.</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: red;'>Migration Hatası:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "<br><a href='../admin/dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Admin Paneline Dön</a>";
echo " <a href='../admin/file-cancellations.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>İptal Yönetimi</a>";
echo "</body></html>";
?>
