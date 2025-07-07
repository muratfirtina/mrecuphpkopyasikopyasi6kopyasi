<?php
/**
 * Download Debug Script - Detaylı hata ayıklama
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Gerekli sınıfları ve fonksiyonları include et
if (!function_exists('isValidUUID')) {
    require_once '../includes/functions.php';
}
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

echo "<h2>📥 Download Debug - Detailed Analysis</h2>";

// Test verileri
$testUploadId = '5c308aa4-770a-4db3-b361-97bcc696dde2';

echo "<h3>1. Session Check</h3>";
echo "Session active: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "<br>";
echo "User logged in: " . (isLoggedIn() ? 'YES' : 'NO') . "<br>";
echo "User is admin: " . (isAdmin() ? 'YES' : 'NO') . "<br>";

if (isset($_SESSION['user_id'])) {
    echo "Session user ID: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "<span style='color: red;'>❌ No user_id in session!</span><br>";
}

if (isset($_SESSION['role'])) {
    echo "Session role: " . $_SESSION['role'] . "<br>";
} else {
    echo "<span style='color: orange;'>⚠️ No role in session!</span><br>";
}

echo "<h3>2. Database Upload Check</h3>";
try {
    $fileManager = new FileManager($pdo);
    $upload = $fileManager->getUploadById($testUploadId);
    
    if ($upload) {
        echo "✅ Upload found in database<br>";
        echo "File name: " . htmlspecialchars($upload['original_name']) . "<br>";
        echo "Filename: " . htmlspecialchars($upload['filename'] ?? 'NULL') . "<br>";
        echo "User ID: " . htmlspecialchars($upload['user_id']) . "<br>";
        echo "Status: " . htmlspecialchars($upload['status']) . "<br>";
        
        // Dosya path kontrolü
        if (!empty($upload['filename'])) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . $upload['filename'];
            echo "Full path: " . $fullPath . "<br>";
            echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "<br>";
            
            if (file_exists($fullPath)) {
                echo "File size: " . filesize($fullPath) . " bytes<br>";
                echo "File readable: " . (is_readable($fullPath) ? 'YES' : 'NO') . "<br>";
                echo "File permissions: " . substr(sprintf('%o', fileperms($fullPath)), -4) . "<br>";
            } else {
                echo "<span style='color: red;'>❌ File does not exist on disk!</span><br>";
                
                // List files in directory
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/';
                if (is_dir($uploadDir)) {
                    $files = array_diff(scandir($uploadDir), ['.', '..']);
                    echo "Files in directory: " . implode(', ', array_slice($files, 0, 10)) . "<br>";
                }
            }
        } else {
            echo "<span style='color: red;'>❌ No filename in database!</span><br>";
        }
    } else {
        echo "<span style='color: red;'>❌ Upload not found in database!</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Database error: " . $e->getMessage() . "</span><br>";
}

echo "<h3>3. Direct Download Test</h3>";
echo "<p>Test download directly:</p>";
$downloadUrl = "download.php?type=original&id=" . $testUploadId;
echo "<a href='$downloadUrl' target='_blank' style='background: #dc3545; color: white; padding: 10px; text-decoration: none;'>🔽 Test Download</a><br><br>";

echo "<h3>4. File Detail Test</h3>";
$detailUrl = "file-detail.php?id=" . $testUploadId;
echo "<a href='$detailUrl' target='_blank' style='background: #28a745; color: white; padding: 10px; text-decoration: none;'>👁️ Test Detail Page</a><br><br>";

echo "<h3>5. Admin Header Check</h3>";
$adminHeaderPath = '../includes/admin_header.php';
echo "Admin header exists: " . (file_exists($adminHeaderPath) ? 'YES' : 'NO') . "<br>";

$adminSidebarPath = '../includes/admin_sidebar.php';
echo "Admin sidebar exists: " . (file_exists($adminSidebarPath) ? 'YES' : 'NO') . "<br>";

$adminFooterPath = '../includes/admin_footer.php';
echo "Admin footer exists: " . (file_exists($adminFooterPath) ? 'YES' : 'NO') . "<br>";

echo "<h3>6. Error Log Check</h3>";
echo "<p>Check your MAMP error logs for any PHP errors. Common locations:</p>";
echo "<ul>";
echo "<li>/Applications/MAMP/logs/php_error.log</li>";
echo "<li>/Applications/MAMP/logs/apache_error.log</li>";
echo "</ul>";

echo "<h3>7. Raw Download URL Debug</h3>";
echo "<p>Try this direct download URL in a new tab:</p>";
echo "<code>http://localhost:8888/mrecuphpkopyasikopyasi6kopyasi/admin/download.php?type=original&id=$testUploadId</code>";

echo "<hr>";
echo "<h3>🔧 Next Steps</h3>";
echo "<p>1. Click the test download link above</p>";
echo "<p>2. Click the test detail page link above</p>";
echo "<p>3. Check browser developer tools (F12) for any JavaScript errors</p>";
echo "<p>4. Check MAMP error logs for PHP errors</p>";
?>
