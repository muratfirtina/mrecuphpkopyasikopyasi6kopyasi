<?php
/**
 * Mr ECU - Add Notified Column to File Uploads
 * File_uploads Tablosuna Notified Kolonu Ekleme
 */

require_once 'database.php';

try {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Mr ECU - Notified Kolonu Ekleme</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .success { color: #28a745; font-size: 18px; margin-bottom: 15px; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>File_uploads Tablosu Güncelleniyor...</h1>";

    // Notified kolunu kontrol et
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $notifiedExists = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'notified') {
            $notifiedExists = true;
            break;
        }
    }
    
    if (!$notifiedExists) {
        // Notified kolunu ekle
        $pdo->exec("ALTER TABLE file_uploads ADD COLUMN notified TINYINT(1) DEFAULT 0 COMMENT 'Kullanıcıya bildirim gönderildi mi'");
        echo "<div class='success'>✅ 'notified' kolonu file_uploads tablosuna eklendi</div>";
    } else {
        echo "<div class='info'>ℹ️ 'notified' kolonu zaten mevcut</div>";
    }
    
    // Mevcut dosyaları notified = 0 olarak işaretle
    $stmt = $pdo->exec("UPDATE file_uploads SET notified = 0 WHERE notified IS NULL");
    echo "<div class='success'>✅ Mevcut dosyalar notified = 0 olarak işaretlendi</div>";
    
    echo "<div class='info'>
        <strong>Notified Kolonu Hakkında:</strong><br>
        Bu kolon, kullanıcıya dosya durum değişikliği hakkında bildirim gönderilip gönderilmediğini takip eder.<br>
        - 0: Bildirim gönderilmedi<br>
        - 1: Bildirim gönderildi
    </div>";
    
    echo "<div>
        <a href='../admin/' class='btn'>Admin Paneline Git</a>
        <a href='../admin/email-settings.php' class='btn'>Email Ayarları</a>
    </div>";
    
    echo "</div></body></html>";
    
} catch(PDOException $e) {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Güncelleme Hatası</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .error { color: #dc3545; font-size: 24px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='error'>❌ Güncelleme Hatası</div>
        <p><strong>Hata:</strong> " . $e->getMessage() . "</p>
    </div>
</body>
</html>";
}
?>
