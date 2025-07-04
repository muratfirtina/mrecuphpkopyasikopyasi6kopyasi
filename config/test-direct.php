<?php
// Config olmadan MySQL test
echo "<h1>MAMP MySQL Test (Config'siz)</h1>";
echo "<p>PHP Versiyonu: " . phpversion() . "</p>";

// Direkt MySQL bağlantısı dene
try {
    // MAMP ayarlarınıza göre
    $dsn = "mysql:host=127.0.0.1;port=8889;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ MySQL Bağlantı BAŞARILI!</p>";
    echo "<p>Host: 127.0.0.1:8889</p>";
    echo "<p>User: root</p>";
    echo "<p>Password: (boş)</p>";
    
    // Version al
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "<p>MySQL Version: " . $version . "</p>";
    
    // Veritabanlarını listele
    $databases = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Databases: " . implode(', ', $databases) . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ MySQL HATA: " . $e->getMessage() . "</p>";
    
    // Alternatif boş şifre dene
    try {
        $dsn = "mysql:host=127.0.0.1;port=8889;charset=utf8mb4";
        $pdo = new PDO($dsn, 'root', 'root');
        echo "<p style='color: green;'>✅ MySQL Bağlantı BAŞARILI (şifre: root)!</p>";
    } catch (Exception $e2) {
        echo "<p style='color: red;'>❌ MySQL HATA (şifre: root): " . $e2->getMessage() . "</p>";
    }
}

echo "<br><hr><br>";
echo "<a href='install.php'>Install.php'ye Git</a><br>";
echo "<a href='../index.php'>Ana Sayfaya Git</a>";
?>
