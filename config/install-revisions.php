<?php
/**
 * Revize sistemi için veritabanı güncelleme
 */

require_once '../config/config.php';
require_once '../config/database.php';

try {
    echo "<h1>🔄 Revize Sistemi Kurulumu</h1>";
    
    // Revisions tablosunu oluştur
    $sql = "
    CREATE TABLE IF NOT EXISTS revisions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        upload_id INT NOT NULL,
        user_id INT NOT NULL,
        admin_id INT NULL,
        request_notes TEXT NOT NULL,
        admin_notes TEXT NULL,
        credits_charged DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        FOREIGN KEY (upload_id) REFERENCES file_uploads(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
    );
    ";
    
    $pdo->exec($sql);
    echo "<p style='color:green;'>✅ Revisions tablosu oluşturuldu</p>";
    
    // file_uploads tablosuna revision_count sütunu ekle
    try {
        $pdo->exec("ALTER TABLE file_uploads ADD COLUMN revision_count INT DEFAULT 0");
        echo "<p style='color:green;'>✅ file_uploads tablosuna revision_count sütunu eklendi</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange;'>⚠️ revision_count sütunu zaten mevcut</p>";
    }
    
    echo "<p style='color:blue;'>📊 Revize sistemi başarıyla kuruldu!</p>";
    echo "<p><strong>Özellikler:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Kullanıcılar tamamlanan dosyalar için revize talep edebilir</li>";
    echo "<li>✅ Admin revize taleplerini görüntüleyebilir ve işleyebilir</li>";
    echo "<li>✅ Revize için kredi düşürülebilir</li>";
    echo "<li>✅ Revize geçmişi takip edilir</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<br><a href='../admin/'>🏠 Admin paneline dön</a>";
?>
