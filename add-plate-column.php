<?php
/**
 * Plate kolonu ekleme scripti
 */

require_once 'config/config.php';
require_once 'config/database.php';

try {
    // file_uploads tablosunda plate kolonu var mı kontrol et
    $stmt = $pdo->query("SHOW COLUMNS FROM file_uploads LIKE 'plate'");
    $plateExists = $stmt->rowCount() > 0;
    
    if (!$plateExists) {
        echo "Plate kolonu bulunamadı, ekleniyor...\n";
        
        // Plate kolonu ekle - year kolonundan sonra
        $pdo->exec("ALTER TABLE file_uploads ADD COLUMN plate VARCHAR(20) NULL AFTER year");
        
        echo "✅ Plate kolonu başarıyla eklendi!\n";
    } else {
        echo "✅ Plate kolonu zaten mevcut!\n";
    }
    
    // Kolon bilgilerini göster
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nfile_uploads tablosu kolonları:\n";
    echo "================================\n";
    foreach ($columns as $column) {
        $marker = $column['Field'] === 'plate' ? ' <- YENİ!' : '';
        echo $column['Field'] . " (" . $column['Type'] . ")" . $marker . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
?>
