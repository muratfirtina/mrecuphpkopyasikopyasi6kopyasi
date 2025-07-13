<?php
/**
 * Admin Panel Navigation Test
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Admin Panel Navigation Test</h1>";

// Session başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>1. Session Info</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "Username: " . ($_SESSION['username'] ?? 'Not set') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
echo "Is Admin: " . (isset($_SESSION['is_admin']) ? ($_SESSION['is_admin'] ? 'Yes' : 'No') : 'Not set') . "<br>";

echo "<h2>2. Admin Pages Test</h2>";

$adminPages = [
    'Admin Dashboard' => 'admin/index.php',
    'Uploads Page' => 'admin/uploads.php', 
    'Revisions Page' => 'admin/revisions.php',
    'Users Page' => 'admin/users.php',
    'Brands Page' => 'admin/brands.php',
    'Settings Page' => 'admin/settings.php'
];

foreach ($adminPages as $name => $path) {
    $fullPath = __DIR__ . '/' . $path;
    echo "<strong>" . $name . ":</strong> ";
    
    if (file_exists($fullPath)) {
        echo "✅ Exists - <a href='" . $path . "' target='_blank'>Test Link</a>";
    } else {
        echo "❌ Missing";
    }
    echo "<br>";
}

echo "<h2>3. Database Status</h2>";

try {
    // Upload sayısı
    $stmt = $pdo->query("SELECT COUNT(*) FROM file_uploads");
    $uploadCount = $stmt->fetchColumn();
    echo "Total uploads: " . $uploadCount . "<br>";
    
    // User sayısı  
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $stmt->fetchColumn();
    echo "Total users: " . $userCount . "<br>";
    
    // Revision sayısı
    $stmt = $pdo->query("SELECT COUNT(*) FROM revisions"); 
    $revisionCount = $stmt->fetchColumn();
    echo "Total revisions: " . $revisionCount . "<br>";
    
    // Response sayısı
    $stmt = $pdo->query("SELECT COUNT(*) FROM file_responses");
    $responseCount = $stmt->fetchColumn();
    echo "Total responses: " . $responseCount . "<br>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>4. FileManager Test</h2>";

try {
    require_once 'includes/FileManager.php';
    $fileManager = new FileManager($pdo);
    
    echo "✅ FileManager created<br>";
    
    // Empty uploads test
    $uploads = $fileManager->getAllUploads(1, 10);
    echo "Uploads query result: " . count($uploads) . " items<br>";
    
    if (empty($uploads)) {
        echo "ℹ️ No uploads found (this is normal if database is empty)<br>";
    }
    
} catch (Exception $e) {
    echo "❌ FileManager error: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Quick Navigation</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h3>🎯 Test These Pages:</h3>";
echo "<p><a href='admin/index.php' target='_blank'>🏠 Admin Dashboard</a> - Ana admin sayfası</p>";
echo "<p><a href='admin/uploads.php' target='_blank'>📁 Uploads Page</a> - Dosya listesi (boş olabilir)</p>";
echo "<p><a href='admin/users.php' target='_blank'>👥 Users Page</a> - Kullanıcı listesi</p>";
echo "<p><a href='admin/revisions.php' target='_blank'>🔄 Revisions Page</a> - Revize talepleri</p>";
echo "</div>";

echo "<h2>6. Upload Form Test</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h3>⚠️ Önemli Not:</h3>";
echo "<p>Eğer database'de hiç dosya yoksa:</p>";
echo "<ul>";
echo "<li>✅ <strong>uploads.php</strong> açılmalı ama boş liste göstermeli</li>";
echo "<li>❌ <strong>file-detail.php</strong> hiçbir zaman doğrudan açılmamalı</li>";
echo "<li>ℹ️ Dosya yükleme işlemi users sayfasından yapılmalı</li>";
echo "</ul>";
echo "</div>";

?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2, h3 { color: #333; }
    a { color: #007bff; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
