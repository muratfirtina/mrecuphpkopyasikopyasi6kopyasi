<?php
/**
 * Download Debug - Detaylı hata analizi
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Gerekli sınıfları ve fonksiyonları include et
if (!function_exists('isValidUUID')) {
    require_once '../includes/functions.php';
}
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

echo "<h2>🔍 Download Debug Analysis</h2>";

// Test parametreleri
$testUploadId = '5c308aa4-770a-4db3-b361-97bcc696dde2';

echo "<h3>1. Database Query Test</h3>";
$fileManager = new FileManager($pdo);
$upload = $fileManager->getUploadById($testUploadId);

if ($upload) {
    echo "✅ Upload found in database<br>";
    echo "- Original name: " . htmlspecialchars($upload['original_name']) . "<br>";
    echo "- Filename: " . htmlspecialchars($upload['filename']) . "<br>";
    echo "- File size: " . ($upload['file_size'] ?? 'NULL') . "<br>";
    echo "- User ID: " . htmlspecialchars($upload['user_id']) . "<br>";
    echo "- Status: " . htmlspecialchars($upload['status']) . "<br>";
} else {
    echo "❌ Upload NOT found in database<br>";
    echo "Let's check what uploads exist:<br>";
    
    $stmt = $pdo->query("SELECT id, original_name, filename FROM file_uploads LIMIT 5");
    $uploads = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($uploads as $u) {
        echo "<li>ID: " . $u['id'] . " - " . htmlspecialchars($u['original_name']) . " (filename: " . $u['filename'] . ")</li>";
    }
    echo "</ul>";
}

if ($upload) {
    echo "<h3>2. File Path Analysis</h3>";
    
    // Farklı olası path'leri test et
    $possiblePaths = [
        $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . $upload['filename'],
        $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . $upload['filename'],
        __DIR__ . '/../uploads/user_files/' . $upload['filename'],
        __DIR__ . '/../uploads/' . $upload['filename']
    ];
    
    foreach ($possiblePaths as $index => $path) {
        echo "Path " . ($index + 1) . ": " . $path . "<br>";
        echo "- Exists: " . (file_exists($path) ? "✅ YES" : "❌ NO") . "<br>";
        if (file_exists($path)) {
            echo "- Size: " . filesize($path) . " bytes<br>";
            echo "- Readable: " . (is_readable($path) ? "✅ YES" : "❌ NO") . "<br>";
        }
        echo "<br>";
    }
    
    echo "<h3>3. Upload Directory Contents</h3>";
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/';
    if (is_dir($uploadDir)) {
        echo "Directory: $uploadDir<br>";
        $files = scandir($uploadDir);
        echo "Files found: " . count($files) . "<br>";
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $fullPath = $uploadDir . $file;
                echo "<li>$file (" . filesize($fullPath) . " bytes)</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "❌ Upload directory does not exist: $uploadDir<br>";
    }
    
    echo "<h3>4. Download URL Test</h3>";
    $downloadUrl = "download.php?type=original&id=" . $testUploadId;
    echo "Download URL: <a href='$downloadUrl' target='_blank'>$downloadUrl</a><br>";
    echo "Manual Test: <a href='$downloadUrl' style='background: #007bff; color: white; padding: 10px; text-decoration: none;'>Try Download</a><br>";
}

echo "<h3>5. Admin Session Check</h3>";
echo "Session status: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ Active" : "❌ Inactive") . "<br>";
echo "User logged in: " . (isset($_SESSION['user_id']) ? "✅ YES" : "❌ NO") . "<br>";
echo "Is admin: " . (isAdmin() ? "✅ YES" : "❌ NO") . "<br>";

if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
}
if (isset($_SESSION['role'])) {
    echo "Role: " . $_SESSION['role'] . "<br>";
}

echo "<h3>6. File Detail URL Test</h3>";
$detailUrl = "file-detail.php?id=" . $testUploadId;
echo "Detail URL: <a href='$detailUrl' target='_blank'>$detailUrl</a><br>";
echo "Manual Test: <a href='$detailUrl' style='background: #28a745; color: white; padding: 10px; text-decoration: none;'>Try Detail Page</a><br>";

echo "<h3>7. Error Log Check</h3>";
echo "Check your error logs for any PHP errors. Common locations:<br>";
echo "- MAMP: /Applications/MAMP/logs/php_error.log<br>";
echo "- Or check the browser's developer console for JavaScript errors<br>";

echo "<hr>";
echo "<h3>🔧 Troubleshooting Actions</h3>";
echo "<ol>";
echo "<li><a href='setup-uploads.php'>Setup Upload Directories</a></li>";
echo "<li><a href='uploads.php'>Go to Uploads Page</a></li>";
echo "<li><a href='$downloadUrl'>Direct Download Test</a></li>";
echo "<li><a href='$detailUrl'>Direct Detail Test</a></li>";
echo "</ol>";
?>
