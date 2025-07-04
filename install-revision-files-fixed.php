<?php
/**
 * Revize dosyaları için veritabanı güncelleme - DÜZELTME
 */

require_once 'config/config.php';
require_once 'config/database.php';

try {
    echo "<h1>📁 Revize Dosya Sistemi Kurulumu (Düzeltilmiş)</h1>";
    
    // Eğer tablo varsa ve hatalıysa, önce sil
    if (isset($_GET['force']) && $_GET['force'] == '1') {
        echo "<h2>🗑️ Mevcut Tabloları Temizle</h2>";
        try {
            $pdo->exec("DROP TABLE IF EXISTS revision_files");
            echo "<p style='color:orange;'>⚠️ revision_files tablosu silindi</p>";
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Silme hatası: " . $e->getMessage() . "</p>";
        }
    }
    
    // Tablo var mı kontrol et
    $stmt = $pdo->query("SHOW TABLES LIKE 'revision_files'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p style='color:orange;'>⚠️ revision_files tablosu zaten mevcut</p>";
        echo "<p><a href='?force=1' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🗑️ Tabloyu Sil ve Yeniden Oluştur</a></p>";
    } else {
        // Revision files tablosunu oluştur
        $sql = "
        CREATE TABLE revision_files (
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
        echo "<p style='color:green;'>✅ revision_files tablosu başarıyla oluşturuldu</p>";
    }
    
    // revision_files dizinini oluştur
    $revisionDir = UPLOAD_DIR . 'revision_files/';
    if (!is_dir($revisionDir)) {
        if (mkdir($revisionDir, 0755, true)) {
            echo "<p style='color:green;'>✅ revision_files dizini oluşturuldu: $revisionDir</p>";
        } else {
            echo "<p style='color:red;'>❌ revision_files dizini oluşturulamadı</p>";
        }
    } else {
        echo "<p style='color:orange;'>⚠️ revision_files dizini zaten mevcut</p>";
    }
    
    // Test sorguları
    if (!$tableExists || isset($_GET['force'])) {
        echo "<h2>🧪 Test Sorguları</h2>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM revision_files");
        $revisionFileCount = $stmt->fetch()['count'];
        echo "<p>📁 Revize dosya sayısı: <strong>$revisionFileCount</strong></p>";
        
        // Tablo yapısını göster
        echo "<h3>📋 Tablo Yapısı:</h3>";
        $stmt = $pdo->query("DESCRIBE revision_files");
        $columns = $stmt->fetchAll();
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>Sütun</th><th>Tip</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<p style='color:blue;'>📊 Revize dosya sistemi hazır!</p>";
    echo "<p><strong>Özellikler:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Admin revize işlerken yeni dosya yükleyebilir</li>";
    echo "<li>✅ Kullanıcı revize edilmiş dosyaları görebilir ve indirebilir</li>";
    echo "<li>✅ Revize dosyaları ayrı dizinde saklanır</li>";
    echo "<li>✅ İndirme geçmişi takip edilir</li>";
    echo "<li>✅ Admin silinirse dosyalar korunur (admin_id NULL olur)</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
    echo "<p>Olası çözümler:</p>";
    echo "<ul>";
    echo "<li><a href='?force=1'>Tabloyu sil ve yeniden oluştur</a></li>";
    echo "<li>MySQL'de manuel olarak: <code>DROP TABLE IF EXISTS revision_files;</code></li>";
    echo "</ul>";
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
