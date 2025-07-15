<?php
/**
 * Revizyon Dosyalarına Revize Sistemi için Veritabanı Düzeltme
 * Bu dosya revisions tablosuna revision_file_id ve parent_revision_id kolonlarını ekler
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Revision Files Revision Fix</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔧 Revizyon Dosyalarına Revize Sistemi Düzeltme</h1>";

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
    
    // 2. Gerekli kolonları kontrol et ve ekle
    $requiredColumns = [
        'revision_file_id' => 'VARCHAR(36) NULL COMMENT "Hangi revizyon dosyasına revize talep edildiği"',
        'parent_revision_id' => 'VARCHAR(36) NULL COMMENT "Ana revizyon talebi ID (alt revizyon talepleri için)"'
    ];
    
    echo "<h2>2. Eksik Kolonları Ekleme</h2>";
    
    foreach ($requiredColumns as $column => $definition) {
        if (in_array($column, $existingColumns)) {
            echo "<div class='success'>✅ $column kolonu zaten mevcut!</div>";
        } else {
            try {
                $sql = "ALTER TABLE revisions ADD COLUMN $column $definition";
                $pdo->exec($sql);
                echo "<div class='success'>✅ $column kolonu eklendi!</div>";
            } catch (PDOException $e) {
                echo "<div class='error'>❌ $column kolonu eklenemedi: " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // 3. İndeks ekleme
    echo "<h2>3. İndeks Kontrolü</h2>";
    
    $indexes = [
        'idx_revision_file_id' => 'revision_file_id',
        'idx_parent_revision_id' => 'parent_revision_id'
    ];
    
    foreach ($indexes as $indexName => $columnName) {
        try {
            $existingIndexes = $pdo->query("SHOW INDEX FROM revisions WHERE Key_name = '$indexName'")->fetchAll();
            if (empty($existingIndexes)) {
                $sql = "ALTER TABLE revisions ADD INDEX $indexName ($columnName)";
                $pdo->exec($sql);
                echo "<div class='success'>✅ $indexName indeksi eklendi!</div>";
            } else {
                echo "<div class='success'>✅ $indexName indeksi zaten mevcut!</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='error'>❌ $indexName indeks hatası: " . $e->getMessage() . "</div>";
        }
    }
    
    // 4. Foreign key ekleme (opsiyonel)
    echo "<h2>4. Foreign Key Kontrolü</h2>";
    try {
        // revision_files tablosunun var olup olmadığını kontrol et
        $revisionFilesTableExists = $pdo->query("SHOW TABLES LIKE 'revision_files'")->fetchAll();
        if (!empty($revisionFilesTableExists)) {
            // revision_file_id için foreign key var mı kontrol et
            $foreignKeys = $pdo->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'revisions' 
                AND COLUMN_NAME = 'revision_file_id' 
                AND REFERENCED_TABLE_NAME = 'revision_files'
                AND TABLE_SCHEMA = DATABASE()
            ")->fetchAll();
            
            if (empty($foreignKeys)) {
                try {
                    $sql = "ALTER TABLE revisions ADD CONSTRAINT fk_revisions_revision_file_id 
                            FOREIGN KEY (revision_file_id) REFERENCES revision_files(id) ON DELETE CASCADE";
                    $pdo->exec($sql);
                    echo "<div class='success'>✅ revision_file_id foreign key eklendi!</div>";
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ revision_file_id foreign key hatası (normal): " . $e->getMessage() . "</div>";
                }
            } else {
                echo "<div class='success'>✅ revision_file_id foreign key zaten mevcut!</div>";
            }
            
            // parent_revision_id için self-reference foreign key
            $parentForeignKeys = $pdo->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'revisions' 
                AND COLUMN_NAME = 'parent_revision_id' 
                AND REFERENCED_TABLE_NAME = 'revisions'
                AND TABLE_SCHEMA = DATABASE()
            ")->fetchAll();
            
            if (empty($parentForeignKeys)) {
                try {
                    $sql = "ALTER TABLE revisions ADD CONSTRAINT fk_revisions_parent_revision_id 
                            FOREIGN KEY (parent_revision_id) REFERENCES revisions(id) ON DELETE CASCADE";
                    $pdo->exec($sql);
                    echo "<div class='success'>✅ parent_revision_id foreign key eklendi!</div>";
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ parent_revision_id foreign key hatası (normal): " . $e->getMessage() . "</div>";
                }
            } else {
                echo "<div class='success'>✅ parent_revision_id foreign key zaten mevcut!</div>";
            }
        } else {
            echo "<div class='info'>⚠️ revision_files tablosu bulunamadı, foreign key atlandı</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Foreign key genel hatası: " . $e->getMessage() . "</div>";
    }
    
    // 5. Final kontrol
    echo "<h2>5. Final Kontrol</h2>";
    $finalColumns = $pdo->query("DESCRIBE revisions")->fetchAll();
    echo "<div class='success'>✅ Güncellenmiş tablo yapısı:</div>";
    echo "<ul>";
    foreach ($finalColumns as $column) {
        $highlight = (in_array($column['Field'], ['revision_file_id', 'parent_revision_id'])) ? ' style="background-color: yellow;"' : '';
        echo "<li{$highlight}><strong>{$column['Field']}</strong> - {$column['Type']} " . 
             ($column['Null'] === 'YES' ? '(NULL)' : '(NOT NULL)') . "</li>";
    }
    echo "</ul>";
    
    // 6. Test verileri
    echo "<h2>6. Test Verileri Kontrolü</h2>";
    $revisionCount = $pdo->query("SELECT COUNT(*) FROM revisions")->fetchColumn();
    echo "<div class='info'>📊 Toplam revize talebi: $revisionCount</div>";
    
    // Revision files tablosunu kontrol et
    if (!empty($revisionFilesTableExists)) {
        $revisionFilesCount = $pdo->query("SELECT COUNT(*) FROM revision_files")->fetchColumn();
        echo "<div class='info'>📁 Toplam revizyon dosyası: $revisionFilesCount</div>";
    }
    
    echo "<div class='success'>🎉 Revizyon dosyalarına revize sistemi düzeltmesi tamamlandı!</div>";
    echo "<div class='info'>Artık revizyon dosyalarına da yeniden revize talep edilebilir.</div>";
    
    // 7. Açıklama
    echo "<h2>7. Sistem Açıklaması</h2>";
    echo "<div class='info'>";
    echo "<h4>Yeni Revize Akışı:</h4>";
    echo "<ol>";
    echo "<li><strong>Ana Dosya:</strong> Kullanıcı dosya yükler</li>";
    echo "<li><strong>Yanıt Dosyası:</strong> Admin yanıt dosyası yükler</li>";
    echo "<li><strong>1. Revize:</strong> Kullanıcı yanıt dosyasına revize talep eder</li>";
    echo "<li><strong>Revizyon Dosyası:</strong> Admin revizyon dosyası yükler</li>";
    echo "<li><strong>2. Revize:</strong> Kullanıcı revizyon dosyasına da yeniden revize talep edebilir!</li>";
    echo "<li><strong>Alt Revizyon:</strong> Sınırsız revize zinciri oluşturulabilir</li>";
    echo "</ol>";
    echo "<p><strong>revision_file_id:</strong> Hangi revizyon dosyasına revize talep edildiğini belirtir</p>";
    echo "<p><strong>parent_revision_id:</strong> Alt revize taleplerinin ana revize talebini referans etmesi için</p>";
    echo "</div>";
    
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
