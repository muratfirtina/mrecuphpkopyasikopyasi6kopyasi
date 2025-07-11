<?php
/**
 * Mr ECU - Clean Notification Tables
 * Bildirim Tablolarını Temizle ve Yeniden Oluştur
 */

require_once 'database.php';

try {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Mr ECU - Bildirim Tabloları Temizle</title>
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
        <h1>Bildirim Tabloları Temizleniyor...</h1>";

    // Mevcut notification tablolarını sil
    $tables = ['notifications', 'email_templates', 'email_queue'];
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS $table");
            echo "<div class='success'>✅ $table tablosu silindi</div>";
        } catch (PDOException $e) {
            echo "<div class='info'>⚠️ $table tablosu silinemedi: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<div class='info'>
        <strong>Tablo temizleme tamamlandı!</strong><br>
        Şimdi install-notifications.php dosyasını çalıştırarak tabloları yeniden oluşturabilirsiniz.
    </div>";
    
    echo "<div>
        <a href='install-notifications.php' class='btn'>Bildirim Tablolarını Yeniden Oluştur</a>
        <a href='../admin/' class='btn'>Admin Paneline Git</a>
    </div>";
    
    echo "</div></body></html>";
    
} catch(PDOException $e) {
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Temizleme Hatası</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .error { color: #dc3545; font-size: 24px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='error'>❌ Temizleme Hatası</div>
        <p><strong>Hata:</strong> " . $e->getMessage() . "</p>
    </div>
</body>
</html>";
}
?>
