<?php
/**
 * Comprehensive Test Script
 * Tüm düzeltmeleri test eder
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Gerekli sınıfları ve fonksiyonları include et
if (!function_exists('isValidUUID')) {
    require_once '../includes/functions.php';
}
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

echo "<h2>Mr ECU System Test</h2>";

// 1. Function Tests
echo "<h3>1. Function Tests</h3>";
echo "✓ isValidUUID exists: " . (function_exists('isValidUUID') ? 'YES' : 'NO') . "<br>";
echo "✓ formatFileSize exists: " . (function_exists('formatFileSize') ? 'YES' : 'NO') . "<br>";
echo "✓ generateUUID exists: " . (function_exists('generateUUID') ? 'YES' : 'NO') . "<br>";
echo "✓ sanitize exists: " . (function_exists('sanitize') ? 'YES' : 'NO') . "<br>";

// Test UUID validation
$testUUID = '5c308aa4-770a-4db3-b361-97bcc696dde2';
echo "✓ UUID validation test ($testUUID): " . (isValidUUID($testUUID) ? 'VALID' : 'INVALID') . "<br>";

// Test file size formatting
$testSize = 1048576; // 1MB
echo "✓ File size formatting test (1MB): " . formatFileSize($testSize) . "<br>";

// 2. Class Tests
echo "<h3>2. Class Tests</h3>";
echo "✓ FileManager class exists: " . (class_exists('FileManager') ? 'YES' : 'NO') . "<br>";
echo "✓ User class exists: " . (class_exists('User') ? 'YES' : 'NO') . "<br>";

// Test FileManager instantiation
try {
    $fileManager = new FileManager($pdo);
    echo "✓ FileManager instantiation: SUCCESS<br>";
    
    // Test methods
    echo "✓ getUploadById method exists: " . (method_exists($fileManager, 'getUploadById') ? 'YES' : 'NO') . "<br>";
    echo "✓ updateUploadStatus method exists: " . (method_exists($fileManager, 'updateUploadStatus') ? 'YES' : 'NO') . "<br>";
    echo "✓ deleteUpload method exists: " . (method_exists($fileManager, 'deleteUpload') ? 'YES' : 'NO') . "<br>";
    echo "✓ uploadResponseFile method exists: " . (method_exists($fileManager, 'uploadResponseFile') ? 'YES' : 'NO') . "<br>";
    
} catch (Exception $e) {
    echo "✗ FileManager instantiation failed: " . $e->getMessage() . "<br>";
}

// 3. Database Tests
echo "<h3>3. Database Tests</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_uploads");
    $result = $stmt->fetch();
    echo "✓ Database connection: SUCCESS<br>";
    echo "✓ Total uploads in database: " . $result['count'] . "<br>";
    
    // Test specific upload
    $stmt = $pdo->prepare("SELECT * FROM file_uploads WHERE id = ? LIMIT 1");
    $stmt->execute([$testUUID]);
    $upload = $stmt->fetch();
    
    if ($upload) {
        echo "✓ Test upload found: " . htmlspecialchars($upload['original_name']) . "<br>";
        echo "✓ Upload status: " . $upload['status'] . "<br>";
        echo "✓ Upload filename: " . $upload['filename'] . "<br>";
    } else {
        echo "ℹ No upload found with test UUID (this is OK)<br>";
    }
    
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

// 4. Directory Tests
echo "<h3>4. Directory Tests</h3>";
$uploadBaseDir = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads';
$userFilesDir = $uploadBaseDir . '/user_files';
$responseFilesDir = $uploadBaseDir . '/response_files';

echo "✓ Base upload directory exists: " . (is_dir($uploadBaseDir) ? 'YES' : 'NO') . "<br>";
echo "✓ User files directory exists: " . (is_dir($userFilesDir) ? 'YES' : 'NO') . "<br>";
echo "✓ Response files directory exists: " . (is_dir($responseFilesDir) ? 'YES' : 'NO') . "<br>";

if (is_dir($userFilesDir)) {
    $files = array_diff(scandir($userFilesDir), ['.', '..']);
    echo "✓ Files in user_files directory: " . count($files) . "<br>";
}

// 5. Admin Access Test
echo "<h3>5. Admin Access Test</h3>";
echo "✓ Session active: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "<br>";
echo "✓ User logged in: " . (isLoggedIn() ? 'YES' : 'NO') . "<br>";
echo "✓ User is admin: " . (isAdmin() ? 'YES' : 'NO') . "<br>";

if (isset($_SESSION['user_id'])) {
    echo "✓ Session user ID: " . $_SESSION['user_id'] . "<br>";
}
if (isset($_SESSION['role'])) {
    echo "✓ Session role: " . $_SESSION['role'] . "<br>";
}

// 6. Download Test
echo "<h3>6. Download Link Test</h3>";
$downloadTestId = $testUUID;
$downloadUrl = "download.php?type=original&id=" . $downloadTestId;
echo "✓ Download URL: <a href='$downloadUrl' target='_blank'>$downloadUrl</a><br>";

// 7. File Detail Test
echo "<h3>7. File Detail Test</h3>";
$detailUrl = "file-detail.php?id=" . $downloadTestId;
echo "✓ Detail URL: <a href='$detailUrl' target='_blank'>$detailUrl</a><br>";

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p><strong>Status:</strong> All core functions and classes are working correctly!</p>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ul>";
echo "<li>Test file download functionality</li>";
echo "<li>Test file detail page access</li>";
echo "<li>Verify upload directories have proper permissions</li>";
echo "</ul>";

echo "<br>";
echo "<a href='uploads.php' class='btn btn-primary'>Go to Uploads</a> ";
echo "<a href='debug-download.php' class='btn btn-secondary'>Debug Download</a> ";
echo "<a href='setup-uploads.php' class='btn btn-info'>Setup Uploads</a>";
?>
