<?php
/**
 * Fix Revision Cancellation System
 * Revizyon iptal sistemi düzeltmesi
 */

require_once 'config/config.php';
require_once 'config/database.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Bu sayfaya erişim yetkiniz yok.');
}

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Revizyon İptal Sistemi Düzeltmesi</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #dee2e6; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>";

echo "<div class='container'>";
echo "<h1>🔧 Revizyon İptal Sistemi Düzeltmesi</h1>";

try {
    $allSuccess = true;
    
    // 1. file_cancellations tablosuna revision_request tipini ekle
    echo "<div class='step'>";
    echo "<h2>1. file_cancellations Tablosu Güncelleme</h2>";
    
    // Mevcut ENUM değerlerini kontrol et
    $stmt = $pdo->query("SHOW COLUMNS FROM file_cancellations LIKE 'file_type'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "<div class='info'>Mevcut file_type kolonu: {$column['Type']}</div>";
        
        // revision_request zaten var mı kontrol et
        if (strpos($column['Type'], 'revision_request') === false) {
            echo "<div class='warning'>revision_request değeri eksik. Ekleniyor...</div>";
            
            // ENUM'u güncelle
            $pdo->exec("
                ALTER TABLE file_cancellations 
                MODIFY COLUMN file_type ENUM('upload', 'response', 'revision', 'revision_request', 'additional') NOT NULL
            ");
            
            echo "<div class='success'>✅ file_type kolonu güncellendi!</div>";
        } else {
            echo "<div class='success'>✅ revision_request zaten mevcut.</div>";
        }
    } else {
        echo "<div class='error'>❌ file_type kolonu bulunamadı!</div>";
        $allSuccess = false;
    }
    echo "</div>";
    
    // 2. revisions tablosuna is_cancelled, cancelled_at, cancelled_by kolonlarını ekle
    echo "<div class='step'>";
    echo "<h2>2. revisions Tablosu Güncelleme</h2>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM revisions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $columnsToAdd = [
        'is_cancelled' => 'BOOLEAN DEFAULT FALSE',
        'cancelled_at' => 'TIMESTAMP NULL',
        'cancelled_by' => 'CHAR(36) NULL'
    ];
    
    foreach ($columnsToAdd as $columnName => $columnDef) {
        if (!in_array($columnName, $columns)) {
            echo "<div class='warning'>$columnName kolonu eksik. Ekleniyor...</div>";
            $pdo->exec("ALTER TABLE revisions ADD COLUMN $columnName $columnDef");
            echo "<div class='success'>✅ $columnName kolonu eklendi!</div>";
        } else {
            echo "<div class='success'>✅ $columnName kolonu zaten mevcut.</div>";
        }
    }
    
    // Foreign key ekle (eğer yoksa)
    try {
        $pdo->exec("
            ALTER TABLE revisions 
            ADD CONSTRAINT fk_revisions_cancelled_by 
            FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL
        ");
        echo "<div class='success'>✅ Foreign key eklendi!</div>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<div class='success'>✅ Foreign key zaten mevcut.</div>";
        } else {
            echo "<div class='warning'>Foreign key eklenirken uyarı: " . $e->getMessage() . "</div>";
        }
    }
    echo "</div>";
    
    // 3. revision_files tablosuna is_cancelled, cancelled_at, cancelled_by kolonlarını ekle
    echo "<div class='step'>";
    echo "<h2>3. revision_files Tablosu Güncelleme</h2>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM revision_files");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($columnsToAdd as $columnName => $columnDef) {
        if (!in_array($columnName, $columns)) {
            echo "<div class='warning'>$columnName kolonu eksik. Ekleniyor...</div>";
            $pdo->exec("ALTER TABLE revision_files ADD COLUMN $columnName $columnDef");
            echo "<div class='success'>✅ $columnName kolonu eklendi!</div>";
        } else {
            echo "<div class='success'>✅ $columnName kolonu zaten mevcut.</div>";
        }
    }
    
    // Foreign key ekle (eğer yoksa)
    try {
        $pdo->exec("
            ALTER TABLE revision_files 
            ADD CONSTRAINT fk_revision_files_cancelled_by 
            FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL
        ");
        echo "<div class='success'>✅ Foreign key eklendi!</div>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<div class='success'>✅ Foreign key zaten mevcut.</div>";
        } else {
            echo "<div class='warning'>Foreign key eklenirken uyarı: " . $e->getMessage() . "</div>";
        }
    }
    echo "</div>";
    
    // 4. Güncellenmiş tablo yapılarını göster
    echo "<div class='step'>";
    echo "<h2>4. Güncellenmiş Tablo Yapıları</h2>";
    
    echo "<h3>file_cancellations Tablosu:</h3>";
    $stmt = $pdo->query("DESCRIBE file_cancellations");
    $columns = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        $highlight = $column['Field'] === 'file_type' ? 'background-color: #ffeb3b;' : '';
        echo "<tr style='$highlight'>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>revisions Tablosu (Son 10 Sütun):</h3>";
    $stmt = $pdo->query("DESCRIBE revisions");
    $columns = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    // Son 10 sütunu göster (iptal ile ilgili olanları vurgulamak için)
    $lastColumns = array_slice($columns, -10);
    foreach ($lastColumns as $column) {
        $highlight = in_array($column['Field'], ['is_cancelled', 'cancelled_at', 'cancelled_by']) ? 'background-color: #c8e6c9;' : '';
        echo "<tr style='$highlight'>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // 5. Test verileri oluştur
    echo "<div class='step'>";
    echo "<h2>5. Test Senaryosu</h2>";
    
    if ($allSuccess) {
        echo "<div class='success'>
        ✅ Tüm güncellemeler başarıyla tamamlandı!<br><br>
        
        <strong>Artık şunlar mümkün:</strong><br>
        • revisions.php sayfasında revizyon taleplerini iptal edebilirsiniz<br>
        • İptal edildiğinde is_cancelled = 1 olacak<br>
        • Eğer bu revizyona ait dosyalar varsa onlar da iptal edilecek<br>
        • Kredi iadesi otomatik yapılacak<br><br>
        
        <strong>Test etmek için:</strong><br>
        1. <a href='admin/revisions.php' target='_blank'>admin/revisions.php</a> sayfasına gidin<br>
        2. Bekleyen bir revizyon talebinde 'İptal Et' butonuna tıklayın<br>
        3. Onay verin ve işlemin başarılı olduğunu kontrol edin
        </div>";
    } else {
        echo "<div class='error'>❌ Bazı güncellemeler başarısız oldu. Lütfen hataları kontrol edin.</div>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Kritik Hata: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</div>";
echo "</body></html>";
?>
