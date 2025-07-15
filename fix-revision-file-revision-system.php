<?php
/**
 * Revize dosyaları için revize sistemi - Veritabanı güncelleme
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Revize Dosyaları İçin Revize Sistemi</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #e6f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔄 Revize Dosyaları için Revize Sistemi Kurulumu</h1>";

try {
    // 1. revisions tablosunda revision_file_id kolonu var mı kontrol et
    $columns = $pdo->query("DESCRIBE revisions")->fetchAll();
    $existingColumns = array_column($columns, 'Field');
    
    echo "<h2>1. Mevcut Revisions Tablo Yapısı</h2>";
    echo "<div class='info'>Mevcut kolonlar: " . implode(', ', $existingColumns) . "</div>";
    
    // 2. revision_file_id kolonu ekle
    if (!in_array('revision_file_id', $existingColumns)) {
        echo "<h2>2. revision_file_id Kolonu Ekleniyor</h2>";
        try {
            $sql = "ALTER TABLE revisions ADD COLUMN revision_file_id VARCHAR(36) NULL AFTER response_id";
            $pdo->exec($sql);
            echo "<div class='success'>✅ revision_file_id kolonu eklendi!</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ revision_file_id kolonu eklenemedi: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='success'>✅ revision_file_id kolonu zaten mevcut!</div>";
    }
    
    // 3. revision_files tablosu var mı kontrol et
    echo "<h2>3. Revision Files Tablosu Kontrolü</h2>";
    $revisionFilesExists = $pdo->query("SHOW TABLES LIKE 'revision_files'")->fetchAll();
    
    if (empty($revisionFilesExists)) {
        echo "<div class='info'>⚠️ revision_files tablosu yok, oluşturuluyor...</div>";
        
        $createRevisionFiles = "
            CREATE TABLE IF NOT EXISTS `revision_files` (
                `id` varchar(36) NOT NULL,
                `revision_id` varchar(36) NOT NULL,
                `upload_id` varchar(36) NOT NULL,
                `admin_id` varchar(36) DEFAULT NULL,
                `original_name` varchar(255) NOT NULL,
                `filename` varchar(255) NOT NULL,
                `file_size` bigint(20) NOT NULL,
                `file_type` varchar(10) NOT NULL,
                `admin_notes` text DEFAULT NULL,
                `upload_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `downloaded` boolean DEFAULT FALSE,
                `download_date` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_revision_id` (`revision_id`),
                KEY `idx_upload_id` (`upload_id`),
                KEY `idx_admin_id` (`admin_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($createRevisionFiles);
        echo "<div class='success'>✅ revision_files tablosu oluşturuldu!</div>";
    } else {
        echo "<div class='success'>✅ revision_files tablosu zaten mevcut!</div>";
    }
    
    // 4. İndeks ekleme
    echo "<h2>4. İndeks Kontrolü</h2>";
    try {
        $indexes = $pdo->query("SHOW INDEX FROM revisions WHERE Key_name = 'idx_revision_file_id'")->fetchAll();
        if (empty($indexes)) {
            $sql = "ALTER TABLE revisions ADD INDEX idx_revision_file_id (revision_file_id)";
            $pdo->exec($sql);
            echo "<div class='success'>✅ revision_file_id indeksi eklendi!</div>";
        } else {
            echo "<div class='success'>✅ revision_file_id indeksi zaten mevcut!</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ İndeks hatası: " . $e->getMessage() . "</div>";
    }
    
    // 5. Foreign key ekleme
    echo "<h2>5. Foreign Key Kontrolü</h2>";
    try {
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
            $sql = "ALTER TABLE revisions ADD CONSTRAINT fk_revisions_revision_file_id 
                    FOREIGN KEY (revision_file_id) REFERENCES revision_files(id) ON DELETE CASCADE";
            $pdo->exec($sql);
            echo "<div class='success'>✅ revision_file_id foreign key eklendi!</div>";
        } else {
            echo "<div class='success'>✅ revision_file_id foreign key zaten mevcut!</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Foreign key hatası (normal): " . $e->getMessage() . "</div>";
    }
    
    // 6. Final kontrol
    echo "<h2>6. Final Tablo Yapısı</h2>";
    $finalColumns = $pdo->query("DESCRIBE revisions")->fetchAll();
    echo "<div class='success'>✅ Güncellenmiş revisions tablosu:</div>";
    echo "<ul>";
    foreach ($finalColumns as $column) {
        $highlight = (in_array($column['Field'], ['response_id', 'revision_file_id'])) ? ' style="background-color: yellow;"' : '';
        echo "<li{$highlight}><strong>{$column['Field']}</strong> - {$column['Type']} " . 
             ($column['Null'] === 'YES' ? '(NULL)' : '(NOT NULL)') . "</li>";
    }
    echo "</ul>";
    
    // 7. Test verileri
    echo "<h2>7. Test Verileri</h2>";
    $revisionCount = $pdo->query("SELECT COUNT(*) FROM revisions")->fetchColumn();
    echo "<div class='info'>📊 Toplam revize talebi: $revisionCount</div>";
    
    if (!empty($revisionFilesExists)) {
        $revisionFileCount = $pdo->query("SELECT COUNT(*) FROM revision_files")->fetchColumn();
        echo "<div class='info'>📁 Toplam revize dosyası: $revisionFileCount</div>";
    }
    
    echo "<div class='success'>🎉 Revize dosyaları için revize sistemi kurulumu tamamlandı!</div>";
    echo "<div class='info'>Artık revize dosyaları için de revize talebi gönderilebilir:</div>";
    echo "<ul>";
    echo "<li>✅ Ana dosya → Yanıt dosyası → Revize dosyası</li>";
    echo "<li>✅ Yanıt dosyası → Revize talebi → Revize dosyası</li>";
    echo "<li>✅ Revize dosyası → Yeni revize talebi → Yeni revize dosyası</li>";
    echo "</ul>";
    
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
