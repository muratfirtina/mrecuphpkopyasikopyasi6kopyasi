<?php
/**
 * Response Revision Sistemi için Veritabanı Düzeltme
 * Bu dosya revisions tablosuna response_id kolonu ekler
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Response Revision Fix</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔧 Response Revision Sistemi Düzeltme</h1>";

try {
    // 1. Revisions tablosunu kontrol et
    $tables = $pdo->query("SHOW TABLES LIKE 'revisions'")->fetchAll();
    if (empty($tables)) {
        echo "<div class='error'>❌ revisions tablosu bulunamadı!</div>";
        echo "</body></html>";
        exit;
    }
    
    echo "<h2>1. Mevcut Tablo Yapısı</h2>";
    $columns = $pdo->query("DESCRIBE revisions")->fetchAll();
    $existingColumns = array_column($columns, 'Field');
    
    echo "<div class='info'>Mevcut kolonlar: " . implode(', ', $existingColumns) . "</div>";
    
    // 2. response_id kolonu var mı kontrol et
    if (in_array('response_id', $existingColumns)) {
        echo "<div class='success'>✅ response_id kolonu zaten mevcut!</div>";
    } else {
        echo "<h2>2. response_id Kolonu Ekleniyor</h2>";
        try {
            $sql = "ALTER TABLE revisions ADD COLUMN response_id VARCHAR(36) NULL AFTER upload_id";
            $pdo->exec($sql);
            echo "<div class='success'>✅ response_id kolonu eklendi!</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ response_id kolonu eklenemedi: " . $e->getMessage() . "</div>";
        }
    }
    
    // 3. İndeks ekleme
    echo "<h2>3. İndeks Kontrolü</h2>";
    try {
        $indexes = $pdo->query("SHOW INDEX FROM revisions WHERE Key_name = 'idx_response_id'")->fetchAll();
        if (empty($indexes)) {
            $sql = "ALTER TABLE revisions ADD INDEX idx_response_id (response_id)";
            $pdo->exec($sql);
            echo "<div class='success'>✅ response_id indeksi eklendi!</div>";
        } else {
            echo "<div class='success'>✅ response_id indeksi zaten mevcut!</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ İndeks hatası: " . $e->getMessage() . "</div>";
    }
    
    // 4. Foreign key ekleme (opsiyonel)
    echo "<h2>4. Foreign Key Kontrolü</h2>";
    try {
        // Önce file_responses tablosunun var olup olmadığını kontrol et
        $responseTableExists = $pdo->query("SHOW TABLES LIKE 'file_responses'")->fetchAll();
        if (!empty($responseTableExists)) {
            // Foreign key var mı kontrol et
            $foreignKeys = $pdo->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'revisions' 
                AND COLUMN_NAME = 'response_id' 
                AND REFERENCED_TABLE_NAME = 'file_responses'
                AND TABLE_SCHEMA = DATABASE()
            ")->fetchAll();
            
            if (empty($foreignKeys)) {
                $sql = "ALTER TABLE revisions ADD CONSTRAINT fk_revisions_response_id 
                        FOREIGN KEY (response_id) REFERENCES file_responses(id) ON DELETE CASCADE";
                $pdo->exec($sql);
                echo "<div class='success'>✅ response_id foreign key eklendi!</div>";
            } else {
                echo "<div class='success'>✅ response_id foreign key zaten mevcut!</div>";
            }
        } else {
            echo "<div class='info'>⚠️ file_responses tablosu bulunamadı, foreign key atlandı</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Foreign key hatası (normal): " . $e->getMessage() . "</div>";
    }
    
    // 5. Final kontrol
    echo "<h2>5. Final Kontrol</h2>";
    $finalColumns = $pdo->query("DESCRIBE revisions")->fetchAll();
    echo "<div class='success'>✅ Güncellenmiş tablo yapısı:</div>";
    echo "<ul>";
    foreach ($finalColumns as $column) {
        $highlight = ($column['Field'] === 'response_id') ? ' style="background-color: yellow;"' : '';
        echo "<li{$highlight}><strong>{$column['Field']}</strong> - {$column['Type']} " . 
             ($column['Null'] === 'YES' ? '(NULL)' : '(NOT NULL)') . "</li>";
    }
    echo "</ul>";
    
    // 6. Test verisi
    echo "<h2>6. Test Verileri Kontrolü</h2>";
    $revisionCount = $pdo->query("SELECT COUNT(*) FROM revisions")->fetchColumn();
    echo "<div class='info'>📊 Toplam revize talebi: $revisionCount</div>";
    
    // Response dosyalarını kontrol et
    if (!empty($responseTableExists)) {
        $responseCount = $pdo->query("SELECT COUNT(*) FROM file_responses")->fetchColumn();
        echo "<div class='info'>📁 Toplam yanıt dosyası: $responseCount</div>";
    }
    
    echo "<div class='success'>🎉 Response revision sistemi düzeltmesi tamamlandı!</div>";
    echo "<div class='info'>Artık yanıt dosyaları için revize talebi gönderilebilir.</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Genel Hata: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><br>";
echo "<a href='user/file-detail.php?id=20b37e6d-7aaa-4be4-b5f5-b4b1d2d9fcdc' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔄 Test Dosyasını Kontrol Et</a>";
echo " &nbsp; ";
echo "<a href='user/files.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📁 Dosyalar Sayfası</a>";

echo "</body></html>";
?>
