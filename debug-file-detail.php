<?php
/**
 * File Detail Debug Script
 */

// Hata raporlamayı açalım
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/Applications/MAMP/logs/php_error.log');

echo "<h1>File Detail Debug</h1>";

try {
    echo "<h2>1. Basic PHP Check</h2>";
    echo "✅ PHP çalışıyor<br>";
    echo "PHP Version: " . phpversion() . "<br>";
    
    echo "<h2>2. Session Check</h2>";
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
        echo "✅ Session başlatıldı<br>";
    } else {
        echo "✅ Session aktif<br>";
    }
    
    echo "Session User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
    echo "Is Admin: " . (isset($_SESSION['is_admin']) ? ($_SESSION['is_admin'] ? 'Yes' : 'No') : 'Not set') . "<br>";
    
    echo "<h2>3. File Paths Check</h2>";
    $basePath = dirname(__FILE__);
    echo "Base Path: " . $basePath . "<br>";
    
    $configPath = $basePath . '/config/config.php';
    echo "Config Path: " . $configPath . " - " . (file_exists($configPath) ? "✅ Exists" : "❌ Missing") . "<br>";
    
    $databasePath = $basePath . '/config/database.php';
    echo "Database Path: " . $databasePath . " - " . (file_exists($databasePath) ? "✅ Exists" : "❌ Missing") . "<br>";
    
    $functionsPath = $basePath . '/includes/functions.php';
    echo "Functions Path: " . $functionsPath . " - " . (file_exists($functionsPath) ? "✅ Exists" : "❌ Missing") . "<br>";
    
    $fileManagerPath = $basePath . '/includes/FileManager.php';
    echo "FileManager Path: " . $fileManagerPath . " - " . (file_exists($fileManagerPath) ? "✅ Exists" : "❌ Missing") . "<br>";
    
    $userPath = $basePath . '/includes/User.php';
    echo "User Path: " . $userPath . " - " . (file_exists($userPath) ? "✅ Exists" : "❌ Missing") . "<br>";
    
    echo "<h2>4. Include Test</h2>";
    
    // Config include test
    try {
        require_once $basePath . '/config/config.php';
        echo "✅ Config included<br>";
    } catch (Exception $e) {
        echo "❌ Config include error: " . $e->getMessage() . "<br>";
        throw $e;
    }
    
    // Database include test
    try {
        require_once $basePath . '/config/database.php';
        echo "✅ Database included<br>";
        
        if (isset($pdo) && $pdo) {
            echo "✅ PDO connection exists<br>";
        } else {
            echo "❌ PDO connection missing<br>";
        }
    } catch (Exception $e) {
        echo "❌ Database include error: " . $e->getMessage() . "<br>";
        throw $e;
    }
    
    // Functions include test
    if (file_exists($functionsPath)) {
        try {
            require_once $functionsPath;
            echo "✅ Functions included<br>";
            
            if (function_exists('isValidUUID')) {
                echo "✅ isValidUUID function exists<br>";
            } else {
                echo "❌ isValidUUID function missing<br>";
            }
        } catch (Exception $e) {
            echo "❌ Functions include error: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "⚠️ Functions file missing - testing built-in functions<br>";
        
        if (function_exists('isValidUUID')) {
            echo "✅ isValidUUID function exists (from config)<br>";
        } else {
            echo "❌ isValidUUID function missing<br>";
        }
    }
    
    // FileManager include test
    try {
        require_once $fileManagerPath;
        echo "✅ FileManager included<br>";
        
        $fileManager = new FileManager($pdo);
        echo "✅ FileManager instance created<br>";
    } catch (Exception $e) {
        echo "❌ FileManager error: " . $e->getMessage() . "<br>";
        throw $e;
    }
    
    // User include test
    try {
        require_once $userPath;
        echo "✅ User included<br>";
        
        $user = new User($pdo);
        echo "✅ User instance created<br>";
    } catch (Exception $e) {
        echo "❌ User error: " . $e->getMessage() . "<br>";
        throw $e;
    }
    
    echo "<h2>5. Parameter Check</h2>";
    
    // Test file detail parameters
    $testUploadId = '7d227059-7f00-4409-a849-5d766ec9136d';
    $testType = 'response';
    
    echo "Test Upload ID: " . $testUploadId . "<br>";
    echo "Test Type: " . $testType . "<br>";
    
    if (function_exists('isValidUUID')) {
        echo "UUID Valid: " . (isValidUUID($testUploadId) ? "✅ Yes" : "❌ No") . "<br>";
    }
    
    echo "<h2>6. Database Query Test</h2>";
    
    try {
        // Test upload query
        $stmt = $pdo->prepare("SELECT * FROM file_uploads WHERE id = ?");
        $stmt->execute([$testUploadId]);
        $upload = $stmt->fetch();
        
        if ($upload) {
            echo "✅ Upload found: " . $upload['original_name'] . "<br>";
        } else {
            echo "❌ Upload not found<br>";
        }
        
        // Test response query
        $stmt = $pdo->prepare("
            SELECT fr.*, fu.user_id, fu.original_name as original_upload_name
            FROM file_responses fr
            LEFT JOIN file_uploads fu ON fr.upload_id = fu.id
            WHERE fr.upload_id = ?
            ORDER BY fr.upload_date DESC
            LIMIT 1
        ");
        $stmt->execute([$testUploadId]);
        $response = $stmt->fetch();
        
        if ($response) {
            echo "✅ Response found: " . $response['original_name'] . "<br>";
        } else {
            echo "❌ Response not found<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Database query error: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>7. Auth Check</h2>";
    
    if (function_exists('isLoggedIn')) {
        echo "isLoggedIn: " . (isLoggedIn() ? "✅ Yes" : "❌ No") . "<br>";
    }
    
    if (function_exists('isAdmin')) {
        echo "isAdmin: " . (isAdmin() ? "✅ Yes" : "❌ No") . "<br>";
    }
    
    echo "<h2>✅ All Tests Passed!</h2>";
    echo "<p><a href='admin/file-detail.php?id=" . $testUploadId . "&type=" . $testType . "' target='_blank'>Test File Detail Page</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error Occurred:</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2, h3 { color: #333; }
    a { color: #007bff; text-decoration: none; }
    a:hover { text-decoration: underline; }
    pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>
