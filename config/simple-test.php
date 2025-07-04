<?php
// Basit MAMP kontrolü
echo "<h1>MAMP Basit Test</h1>";
echo "<p>PHP çalışıyor: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Farklı MySQL bağlantıları dene
$mysql_configs = [
    'Config 1' => ['127.0.0.1:3306', 'root', ''],
    'Config 2' => ['127.0.0.1:3306', 'root', 'root'],
    'Config 3' => ['127.0.0.1:8889', 'root', ''],
    'Config 4' => ['127.0.0.1:8889', 'root', 'root'],
    'Config 5' => ['localhost:3306', 'root', ''],
    'Config 6' => ['localhost:3306', 'root', 'root'],
    'Config 7' => ['localhost:8889', 'root', ''],
    'Config 8' => ['localhost:8889', 'root', 'root'],
];

foreach ($mysql_configs as $name => $config) {
    list($host, $user, $pass) = $config;
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
        echo "<p style='color: green;'>✅ $name BAŞARILI: $host / $user / " . ($pass ?: '(şifre yok)') . "</p>";
        
        // Version al
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "<p style='margin-left: 20px;'>MySQL Version: $version</p>";
        break; // İlk başarılıda dur
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ $name: " . $e->getMessage() . "</p>";
    }
}

echo "<br><a href='install.php'>Install.php'ye git</a>";
?>
