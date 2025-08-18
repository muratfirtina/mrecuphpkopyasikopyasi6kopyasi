<?php
/**
 * Mr ECU - İptal Sistemi Sütunları Ekleme Migration
 * Bu dosya iptal sistemi için gerekli sütunları veritabanına ekler
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin kontrolü
if (!isLoggedIn() || !isAdmin()) {
    die('Bu sayfaya erişim izniniz yok.');
}

echo "<!DOCTYPE html>\n<html><head><title>İptal Sistemi Migration</title></head><body>";
echo "<h1>İptal Sistemi Sütunları Ekleniyor...</h1>";

try {
    // SQL dosyasını oku
    $sqlFile = __DIR__ . '/add_cancellation_columns.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception('SQL dosyası bulunamadı: ' . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    $statements = explode(';', $sql);
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Migration İşlemleri:</h3>";
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Boş satırları ve yorumları atla
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            echo "<p style='color: green;'>✓ Başarılı: " . substr($statement, 0, 80) . "...</p>";
        } catch (PDOException $e) {
            $errorCount++;
            $errorMsg = $e->getMessage();
            $errors[] = $errorMsg;
            
            // Sütun zaten varsa hatayı göz ardı et
            if (strpos($errorMsg, 'Duplicate column name') !== false || 
                strpos($errorMsg, 'already exists') !== false) {
                echo "<p style='color: orange;'>⚠ Zaten mevcut: " . substr($statement, 0, 80) . "...</p>";
            } else {
                echo "<p style='color: red;'>✗ Hata: " . substr($statement, 0, 80) . "... - " . $errorMsg . "</p>";
            }
        }
    }
    
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Migration Özeti:</h3>";
    echo "<p><strong>Başarılı işlemler:</strong> $successCount</p>";
    echo "<p><strong>Hatalı işlemler:</strong> $errorCount</p>";
    
    if ($errorCount == 0 || count($errors) == 0) {
        echo "<p style='color: green; font-weight: bold;'>🎉 Migration başarıyla tamamlandı!</p>";
    } else {
        echo "<p style='color: orange; font-weight: bold;'>⚠ Migration tamamlandı ancak bazı hatalar oluştu.</p>";
    }
    echo "</div>";
    
    // Tablo yapılarını kontrol et
    echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Tablo Yapı Kontrolü:</h3>";
    
    $tables = ['file_uploads', 'file_responses', 'revision_files', 'additional_files'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM $table LIKE 'is_cancelled'");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result) {
                echo "<p style='color: green;'>✓ $table tablosu: is_cancelled sütunu mevcut</p>";
            } else {
                echo "<p style='color: red;'>✗ $table tablosu: is_cancelled sütunu eksik</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ $table tablosu kontrol edilemedi: " . $e->getMessage() . "</p>";
        }
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: red;'>Migration Hatası:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<br><a href='../admin/dashboard.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Admin Paneline Dön</a>";
echo "</body></html>";
?>
