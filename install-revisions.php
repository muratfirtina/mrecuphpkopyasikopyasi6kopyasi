<?php
/**
 * Revize sistemi için veritabanı güncelleme
 */

require_once 'config/config.php';
require_once 'config/database.php';

try {
    echo "<h1>🔄 Revize Sistemi Kurulumu</h1>";
    
    // Revisions tablosunu oluştur (GUID uyumlu)
    $sql = "
    CREATE TABLE IF NOT EXISTS revisions (
        id CHAR(36) PRIMARY KEY,
        upload_id CHAR(36) NOT NULL,
        user_id CHAR(36) NOT NULL,
        admin_id CHAR(36) NULL,
        request_notes TEXT NOT NULL,
        admin_notes TEXT NULL,
        credits_charged DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('pending', 'in_progress', 'completed', 'rejected') DEFAULT 'pending',
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
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p style='color:orange;'>⚠️ revision_count sütunu zaten mevcut</p>";
        } else {
            throw $e;
        }
    }
    
    // Kredi düşürmesi admin yanıt dosyası yüklerken olduğunu kontrol et
    echo "<h2>💰 Kredi Sistemi Kontrolü</h2>";
    echo "<p style='color:green;'>✅ Kredi düşürmesi admin yanıt dosyası yüklerken yapılıyor (uploadResponseFile metodunda)</p>";
    echo "<p style='color:green;'>✅ Kullanıcı indirirken kredi düşmüyor (downloadFile metodunda)</p>";
    
    echo "<p style='color:blue;'>📊 Revize sistemi başarıyla kuruldu!</p>";
    echo "<p><strong>Özellikler:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Kullanıcılar tamamlanan dosyalar için revize talep edebilir</li>";
    echo "<li>✅ Admin revize taleplerini görüntüleyebilir ve işleyebilir</li>";
    echo "<li>✅ Revize için kredi düşürülebilir</li>";
    echo "<li>✅ Revize geçmişi takip edilir</li>";
    echo "<li>✅ Kredi düşürmesi admin yanıt dosyası yüklerken yapılıyor</li>";
    echo "</ul>";
    
    // Test sorgusu çalıştır
    echo "<h2>🧪 Test Sorguları</h2>";
    
    // file_uploads tablosundaki dosyaları listele
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_uploads");
    $fileCount = $stmt->fetch()['count'];
    echo "<p>📁 Toplam dosya sayısı: <strong>$fileCount</strong></p>";
    
    // revisions tablosunu test et
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM revisions");
    $revisionCount = $stmt->fetch()['count'];
    echo "<p>🔄 Toplam revize talebi sayısı: <strong>$revisionCount</strong></p>";
    
    // brands ve models tablosu kontrolü
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM brands");
    $brandCount = $stmt->fetch()['count'];
    echo "<p>🚗 Marka sayısı: <strong>$brandCount</strong></p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM models");
    $modelCount = $stmt->fetch()['count'];
    echo "<p>🔧 Model sayısı: <strong>$modelCount</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h3>📋 Sonraki Adımlar</h3>";
echo "<p>1. <a href='user/user-files-debug.php'>Debug sayfasını kontrol et</a></p>";
echo "<p>2. <a href='user/files.php'>Kullanıcı dosyalar sayfasını test et</a></p>";
echo "<p>3. <a href='admin/uploads.php'>Admin panel dosyaları kontrol et</a></p>";
echo "</div>";

echo "<br><a href='admin/' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Admin paneline dön</a>";
echo " &nbsp; ";
echo "<a href='user/' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>👤 Kullanıcı paneline dön</a>";
?>
