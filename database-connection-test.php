<?php
/**
 * MAMP Database Connection Test
 */

try {
    // MAMP Ayarları (Hard-coded test)
    $host = "127.0.0.1";
    $port = "8889";
    $dbname = "mrecu_db_guid";
    $username = "root";
    $password = "root";
    
    echo "<h2>🔍 MAMP Database Connection Test</h2>";
    
    echo "<h3>1. Trying to connect to MAMP MySQL...</h3>";
    echo "Host: $host:$port<br>";
    echo "Database: $dbname<br>";
    echo "Username: $username<br>";
    echo "Password: " . (empty($password) ? "EMPTY" : "SET") . "<br><br>";
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ <strong>Database Connection SUCCESS!</strong><br><br>";
    
    // Test query
    echo "<h3>2. Testing database query...</h3>";
    $stmt = $pdo->query("SELECT DATABASE() as current_db, NOW() as current_time");
    $result = $stmt->fetch();
    
    echo "Current Database: " . $result['current_db'] . "<br>";
    echo "Current Time: " . $result['current_time'] . "<br><br>";
    
    // Check tables
    echo "<h3>3. Checking tables...</h3>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "⚠️ <strong>NO TABLES FOUND!</strong> Database is empty.<br>";
        echo "You need to import your database structure.<br>";
    } else {
        echo "✅ Found " . count($tables) . " tables:<br>";
        foreach ($tables as $table) {
            echo "- $table<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ <strong>Database Connection FAILED!</strong><br>";
    echo "Error: " . $e->getMessage() . "<br><br>";
    
    echo "<h3>🛠️ Troubleshooting:</h3>";
    echo "1. Is MAMP running?<br>";
    echo "2. Is MySQL port 8889?<br>";
    echo "3. Does database 'mrecu_db_guid' exist?<br>";
    echo "4. Check MAMP phpMyAdmin: <a href='http://localhost:8888/phpMyAdmin/' target='_blank'>http://localhost:8888/phpMyAdmin/</a><br>";
}

echo "<br><h3>📋 Current Environment Check:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
?>
