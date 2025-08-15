<?php
/**
 * Dosya İptal Sistemi Kurulum Script'i
 * File Cancellation System Installation Script
 */

require_once '../config/database.php';

try {
    echo "<h3>Dosya İptal Sistemi Kurulum</h3>";
    
    // Tabloyu oluştur
    $sql = file_get_contents('create_file_cancellations_table.sql');
    $pdo->exec($sql);
    
    echo "<div style='color: green;'>✅ file_cancellations tablosu başarıyla oluşturuldu!</div>";
    
    // Test verisi ekle (opsiyonel)
    echo "<div style='color: blue;'>📋 Tablo yapısı hazır, sistem kullanıma hazır!</div>";
    
    echo "<br><a href='../admin/'>Admin Panel</a> | <a href='../user/'>Kullanıcı Panel</a>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Hata: " . $e->getMessage() . "</div>";
}
?>
