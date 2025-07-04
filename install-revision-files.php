<?php
/**
 * Revize dosyaları için veritabanı güncelleme
 */

require_once 'config/config.php';
require_once 'config/database.php';

try {
    echo "<h1>📁 Revize Dosya Sistemi Kurulumu</h1>";
    
    // Revision files tablosunu oluştur
    $sql = "
    CREATE TABLE IF NOT EXISTS revision_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        revision_id INT NOT NULL,
        upload_id INT NOT NULL,
        admin_id INT NULL,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(50),
        admin_notes TEXT,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        downloaded BOOLEAN DEFAULT FALSE,
        download_date TIMESTAMP NULL,
        FOREIGN KEY (revision_id) REFERENCES revisions(id) ON DELETE CASCADE,
        FOREIGN KEY (upload_id) REFERENCES file_uploads(id) ON DELETE CASCADE,
        FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
    );
    ";
    
    $pdo->exec($sql);
    echo "<p style='color:green;'>✅ revision_files tablosu oluşturuldu</p>";
    
    // revision_files dizinini oluştur
    $revisionDir = UPLOAD_DIR . 'revision_files/';
    if (!is_dir($revisionDir)) {
        mkdir($revisionDir, 0755, true);
        echo "<p style='color:green;'>✅ revision_files dizini oluşturuldu</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ revision_files dizini zaten mevcut</p>";
    }
    
    echo "<p style='color:blue;'>📊 Revize dosya sistemi başarıyla kuruldu!</p>";
    echo "<p><strong>Özellikler:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Admin revize işlerken yeni dosya yükleyebilir</li>";
    echo "<li>✅ Kullanıcı revize edilmiş dosyaları görebilir ve indirebilir</li>";
    echo "<li>✅ Revize dosyaları ayrı dizinde saklanır</li>";
    echo "<li>✅ İndirme geçmişi takip edilir</li>";
    echo "</ul>";
    
    // Test sorgusu çalıştır
    echo "<h2>🧪 Test Sorguları</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM revision_files");
    $revisionFileCount = $stmt->fetch()['count'];
    echo "<p>📁 Revize dosya sayısı: <strong>$revisionFileCount</strong></p>";
    
    // Mevcut revizeler
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM revisions");
    $revisionCount = $stmt->fetch()['count'];
    echo "<p>🔄 Toplam revize talebi sayısı: <strong>$revisionCount</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h3>📋 Sonraki Adımlar</h3>";
echo "<p>1. <a href='admin/revisions.php'>Admin revize yönetimini kontrol et</a></p>";
echo "<p>2. <a href='user/files.php'>Kullanıcı dosya sayfasını test et</a></p>";
echo "<p>3. Revize talebi için dosya yükleme özelliğini test et</p>";
echo "</div>";

echo "<br><a href='admin/' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Admin paneline dön</a>";
echo " &nbsp; ";
echo "<a href='user/' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>👤 Kullanıcı paneline dön</a>";
?>
