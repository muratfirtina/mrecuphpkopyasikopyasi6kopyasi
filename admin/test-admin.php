<?php
/**
 * Minimal Admin Test Page
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Admin Test Started ===<br>";

// 1. Test config load
echo "1. Loading config...<br>";
try {
    require_once '../config/config.php';
    echo "✅ Config loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
    die();
}

// 2. Test database
echo "2. Testing database...<br>";
try {
    require_once '../config/database.php';
    if ($pdo) {
        echo "✅ Database connected<br>";
    } else {
        echo "❌ PDO is null<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// 3. Test User class
echo "3. Testing User class...<br>";
try {
    if (class_exists('User')) {
        echo "✅ User class exists<br>";
        $user = new User($pdo);
        echo "✅ User object created<br>";
    } else {
        echo "❌ User class not found<br>";
        // Try to include manually
        if (file_exists('../includes/User.php')) {
            require_once '../includes/User.php';
            echo "✅ User.php included manually<br>";
            $user = new User($pdo);
            echo "✅ User object created after manual include<br>";
        } else {
            echo "❌ User.php file not found<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ User class error: " . $e->getMessage() . "<br>";
}

// 4. Test FileManager class
echo "4. Testing FileManager class...<br>";
try {
    if (class_exists('FileManager')) {
        echo "✅ FileManager class exists<br>";
        $fileManager = new FileManager($pdo);
        echo "✅ FileManager object created<br>";
    } else {
        echo "❌ FileManager class not found<br>";
        // Try to include manually
        if (file_exists('../includes/FileManager.php')) {
            require_once '../includes/FileManager.php';
            echo "✅ FileManager.php included manually<br>";
            $fileManager = new FileManager($pdo);
            echo "✅ FileManager object created after manual include<br>";
        } else {
            echo "❌ FileManager.php file not found<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ FileManager class error: " . $e->getMessage() . "<br>";
}

// 5. Test session and authentication
echo "5. Testing authentication...<br>";
echo "Session status: " . session_status() . "<br>";
echo "User logged in: " . (isLoggedIn() ? 'Yes' : 'No') . "<br>";
if (isLoggedIn()) {
    echo "User ID: " . ($_SESSION['user_id'] ?? 'not set') . "<br>";
    echo "Username: " . ($_SESSION['username'] ?? 'not set') . "<br>";
    echo "Is Admin: " . (isAdmin() ? 'Yes' : 'No') . "<br>";
} else {
    echo "Not logged in<br>";
}

// 6. Test admin header include
echo "6. Testing admin header include...<br>";
try {
    if (file_exists('../includes/admin_header.php')) {
        echo "✅ admin_header.php file exists<br>";
        // Don't actually include it as it might redirect
    } else {
        echo "❌ admin_header.php file not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Admin header error: " . $e->getMessage() . "<br>";
}

echo "<br>=== Admin Test Completed ===<br>";
?>
