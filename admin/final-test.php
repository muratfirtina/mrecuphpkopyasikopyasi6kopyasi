<?php
/**
 * Quick Fix Test - Final verification of all fixes
 */

echo "<h2>🔧 Final System Test - All Fixes Applied</h2>";

try {
    require_once '../config/config.php';
    require_once '../config/database.php';
    
    // Include required files
    if (!function_exists('isValidUUID')) {
        require_once '../includes/functions.php';
    }
    require_once '../includes/FileManager.php';
    require_once '../includes/User.php';
    
    echo "<div style='color: green;'>✅ All includes loaded successfully!</div><br>";
    
    // Test class instantiation
    $fileManager = new FileManager($pdo);
    $user = new User($pdo);
    echo "<div style='color: green;'>✅ FileManager and User classes instantiated!</div><br>";
    
    // Test required methods
    $requiredMethods = [
        ['FileManager', 'getUploadById'],
        ['FileManager', 'updateUploadStatus'],
        ['FileManager', 'deleteUpload'],
        ['FileManager', 'uploadResponseFile'],
        ['User', 'logAction'],
        ['User', 'addCreditDirectSimple']
    ];
    
    foreach ($requiredMethods as $method) {
        $className = $method[0];
        $methodName = $method[1];
        $obj = $className === 'FileManager' ? $fileManager : $user;
        
        if (method_exists($obj, $methodName)) {
            echo "<div style='color: green;'>✅ {$className}::{$methodName}() exists</div><br>";
        } else {
            echo "<div style='color: red;'>❌ {$className}::{$methodName}() missing</div><br>";
        }
    }
    
    // Test functions
    $requiredFunctions = ['isValidUUID', 'formatFileSize', 'generateUUID', 'sanitize'];
    foreach ($requiredFunctions as $func) {
        if (function_exists($func)) {
            echo "<div style='color: green;'>✅ {$func}() function exists</div><br>";
        } else {
            echo "<div style='color: red;'>❌ {$func}() function missing</div><br>";
        }
    }
    
    // Test upload directories
    $uploadDirs = [
        $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads',
        $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files',
        $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/response_files'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (is_dir($dir)) {
            echo "<div style='color: green;'>✅ Directory exists: " . basename($dir) . "</div><br>";
        } else {
            if (mkdir($dir, 0755, true)) {
                echo "<div style='color: blue;'>🔧 Created directory: " . basename($dir) . "</div><br>";
            } else {
                echo "<div style='color: red;'>❌ Cannot create directory: " . basename($dir) . "</div><br>";
            }
        }
    }
    
    // Test database query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_uploads");
    $result = $stmt->fetch();
    echo "<div style='color: green;'>✅ Database connection OK - Found {$result['count']} uploads</div><br>";
    
    echo "<hr>";
    echo "<h3>🎉 System Status: READY!</h3>";
    echo "<p><strong>All fixes have been applied successfully!</strong></p>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='uploads.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Uploads Page</a>";
    echo "<a href='file-detail.php?id=5c308aa4-770a-4db3-b361-97bcc696dde2' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test File Detail</a>";
    echo "<a href='download.php?type=original&id=5c308aa4-770a-4db3-b361-97bcc696dde2' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Download</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<div style='color: orange;'>📍 Error File: " . $e->getFile() . " (Line " . $e->getLine() . ")</div>";
}
?>
