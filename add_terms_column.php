<?php
/**
 * Users tablosuna terms_accepted kolonu ekleyen script
 */

require_once '../config/config.php';
require_once '../config/database.php';

try {
    echo "Users tablosuna terms_accepted kolonu ekleniyor...\n";
    
    // Kolon var mı kontrol et
    $checkColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'terms_accepted'");
    if ($checkColumn->rowCount() > 0) {
        echo "terms_accepted kolonu zaten mevcut.\n";
        exit;
    }
    
    // Kolonu ekle
    $pdo->exec("
        ALTER TABLE `users` 
        ADD COLUMN `terms_accepted` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Kullanım şartları kabul edildi mi (0=Hayır, 1=Evet)' 
        AFTER `phone`
    ");
    
    // Mevcut kullanıcıları otomatik olarak kabul etmiş sayalım
    $pdo->exec("UPDATE `users` SET `terms_accepted` = 1 WHERE `id` IS NOT NULL");
    
    // Index ekle
    $pdo->exec("ALTER TABLE `users` ADD INDEX `idx_terms_accepted` (`terms_accepted`)");
    
    echo "✓ terms_accepted kolonu başarıyla eklendi!\n";
    echo "✓ Mevcut kullanıcılar otomatik olarak şartları kabul etmiş sayıldı.\n";
    echo "✓ Index eklendi.\n";
    
} catch (Exception $e) {
    echo "HATA: " . $e->getMessage() . "\n";
}
?>
