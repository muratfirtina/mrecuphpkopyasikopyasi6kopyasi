<?php
/**
 * Revisions Tablosunu Response Dosyaları İçin Güncelle
 */

require_once 'config/config.php';
require_once 'config/database.php';

// generateUUID fonksiyonunu tanımla
if (!function_exists('generateUUID')) {
    function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Revisions Tablosu Güncelleme</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔄 Revisions Tablosu Güncelleme</h1>";

try {
    // revisions tablosu var mı kontrol et
    $tables = $pdo->query("SHOW TABLES LIKE 'revisions'")->fetchAll();
    if (empty($tables)) {
        echo "<div class='error'>❌ revisions tablosu bulunamadı!</div>";
        echo "</body></html>";
        exit;
    }
    
    echo "<h2>1. Mevcut Tablo Yapısı Kontrolü</h2>";
    $columns = $pdo->query("DESCRIBE revisions")->fetchAll();
    $existingColumns = array_column($columns, 'Field');
    
    echo "<div class='info'>Mevcut kolonlar: " . implode(', ', $existingColumns) . "</div>";
    
    // Gerekli kolonları kontrol et
    $requiredColumns = [
        'response_id' => 'VARCHAR(36) NULL',
        'request_type' => 'ENUM("upload", "response") DEFAULT "upload"'
    ];
    
    $missingColumns = [];
    foreach ($requiredColumns as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            $missingColumns[$column] = $definition;
        }
    }
    
    if (empty($missingColumns)) {
        echo "<div class='success'>✅ Tüm gerekli kolonlar mevcut!</div>";
    } else {
        echo "<h2>2. Eksik Kolonları Ekleme</h2>";
        echo "<div class='info'>Eklenecek kolonlar: " . implode(', ', array_keys($missingColumns)) . "</div>";
        
        foreach ($missingColumns as $column => $definition) {
            try {
                $sql = "ALTER TABLE revisions ADD COLUMN $column $definition";
                $pdo->exec($sql);
                echo "<div class='success'>✅ $column kolonu eklendi</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ $column kolonu eklenemedi: " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // Foreign key kontrolü
    echo "<h2>3. Foreign Key Kontrolü</h2>";
    try {
        // response_id için foreign key ekle (eğer yoksa)
        $foreignKeys = $pdo->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'revisions' 
            AND COLUMN_NAME = 'response_id' 
            AND REFERENCED_TABLE_NAME = 'file_responses'
        ")->fetchAll();
        
        if (empty($foreignKeys)) {
            $sql = "ALTER TABLE revisions ADD CONSTRAINT fk_revisions_response_id 
                    FOREIGN KEY (response_id) REFERENCES file_responses(id) ON DELETE CASCADE";
            $pdo->exec($sql);
            echo "<div class='success'>✅ response_id foreign key eklendi</div>";
        } else {
            echo "<div class='success'>✅ response_id foreign key zaten mevcut</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Foreign key hatası: " . $e->getMessage() . "</div>";
    }
    
    // Mevcut verileri güncelle (request_type default'u)
    echo "<h2>4. Mevcut Verileri Güncelleme</h2>";
    try {
        $updateCount = $pdo->exec("UPDATE revisions SET request_type = 'upload' WHERE request_type IS NULL OR request_type = ''");
        echo "<div class='success'>✅ $updateCount kayıt güncellendi (request_type = 'upload')</div>";
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Veri güncelleme hatası: " . $e->getMessage() . "</div>";
    }
    
    echo "<h2>5. Final Kontrol</h2>";
    $finalColumns = $pdo->query("DESCRIBE revisions")->fetchAll();
    echo "<div class='success'>✅ Güncellenmiş tablo yapısı:</div>";
    echo "<ul>";
    foreach ($finalColumns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']} " . 
             ($column['Null'] === 'YES' ? '(NULL)' : '(NOT NULL)') . "</li>";
    }
    echo "</ul>";
    
    // Test verisi oluştur
    echo "<h2>6. Test Verisi Kontrolü</h2>";
    $revisionCount = $pdo->query("SELECT COUNT(*) FROM revisions")->fetchColumn();
    echo "<div class='info'>📊 Toplam revize talebi: $revisionCount</div>";
    
    if ($revisionCount == 0) {
        echo "<div class='info'>Test revize talebi oluşturuluyor...</div>";
        
        // Test response dosyası var mı kontrol et
        $testResponse = $pdo->query("SELECT id FROM file_responses LIMIT 1")->fetch();
        if ($testResponse) {
            $testRevisionId = generateUUID();
            $stmt = $pdo->prepare("
                INSERT INTO revisions (id, response_id, user_id, request_notes, request_type, status, requested_at) 
                VALUES (?, ?, (SELECT user_id FROM file_uploads WHERE id = (SELECT upload_id FROM file_responses WHERE id = ?)), 'Test yanıt dosyası revize talebi', 'response', 'pending', NOW())
            ");
            if ($stmt->execute([$testRevisionId, $testResponse['id'], $testResponse['id']])) {
                echo "<div class='success'>✅ Test response revize talebi oluşturuldu</div>";
            }
        } else {
            echo "<div class='info'>Test response dosyası bulunamadı</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Genel Hata: " . $e->getMessage() . "</div>";
}

echo "<br><br><a href='user/files.php'>📁 Files sayfasını test et</a> | <a href='admin/'>🏠 Admin paneline git</a>";
echo "</body></html>";
?>
