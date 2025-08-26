<?php
/**
 * Debug Test - Site çalışıyor mu?
 */

// Error reporting'i aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Mr ECU - Debug Test</h1>";
echo "<hr>";

// PHP versiyonu
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";

// Include path test
echo "<h2>2. File Path Test</h2>";
$configPath = __DIR__ . '/config/config.php';
$databasePath = __DIR__ . '/config/database.php';

echo "Config file exists: " . (file_exists($configPath) ? '✅ Yes' : '❌ No') . " ({$configPath})<br>";
echo "Database file exists: " . (file_exists($databasePath) ? '✅ Yes' : '❌ No') . " ({$databasePath})<br>";

// Database test
echo "<h2>3. Database Connection Test</h2>";
try {
    require_once $configPath;
    echo "Config loaded: ✅<br>";
    
    require_once $databasePath;
    echo "Database connection: ✅<br>";
    
    // Simple query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "Database query test: " . ($result['test'] == 1 ? '✅' : '❌') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "❌ File: " . $e->getFile() . "<br>";
    echo "❌ Line: " . $e->getLine() . "<br>";
}

// Memory and server info
echo "<h2>4. Server Info</h2>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "<br>";

// Include test
echo "<h2>5. Include Test</h2>";
$includesPath = __DIR__ . '/includes/';
$files = ['header.php', 'footer.php', 'admin_header.php', 'admin_sidebar.php'];

foreach ($files as $file) {
    $filePath = $includesPath . $file;
    echo "$file: " . (file_exists($filePath) ? '✅ Exists' : '❌ Missing') . "<br>";
}

echo "<h2>6. Next Steps</h2>";
echo "<p>Eğer yukarıdaki tüm testler başarılıysa, şu linkleri test edin:</p>";
echo "<ul>";
echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/' target='_blank'>Ana Sayfa</a></li>";
echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/install-product-system.php' target='_blank'>Kurulum</a></li>";
echo "<li><a href='/mrecuphpkopyasikopyasi6kopyasi/admin/' target='_blank'>Admin Panel</a></li>";
echo "</ul>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background: #f8f9fa;
    line-height: 1.6;
}
h1, h2 { color: #333; }
code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
