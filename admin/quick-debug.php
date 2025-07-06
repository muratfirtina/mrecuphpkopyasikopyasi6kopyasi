<?php
/**
 * Quick Debug - Specific page testing
 */

require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

echo "<h1>Quick Debug - Page Testing</h1>";
echo "<hr>";

// Test users.php query
echo "<h2>1. users.php Query Test</h2>";
try {
    $whereClause = "WHERE 1=1";
    $params = [];
    $limit = 5;
    $offset = 0;
    
    $query = "
        SELECT id, username, email, first_name, last_name, phone, role, credits, 
               status, email_verified, created_at, last_login
        FROM users 
        $whereClause 
        ORDER BY created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    echo "<div style='color: green;'>✅ users.php query success: " . count($users) . " users</div>";
    if (!empty($users)) {
        echo "<pre>";
        print_r(array_slice($users, 0, 2));
        echo "</pre>";
    }
} catch(Exception $e) {
    echo "<div style='color: red;'>❌ users.php query error: " . $e->getMessage() . "</div>";
}

// Test uploads.php query
echo "<h2>2. uploads.php Query Test</h2>";
try {
    $whereClause = "WHERE 1=1";
    $params = [];
    $limit = 5;
    $offset = 0;
    
    $query = "
        SELECT u.*, 
               users.username, users.email, users.first_name, users.last_name,
               b.name as brand_name,
               m.name as model_name
        FROM file_uploads u
        LEFT JOIN users ON u.user_id = users.id
        LEFT JOIN brands b ON u.brand_id = b.id
        LEFT JOIN models m ON u.model_id = m.id
        $whereClause 
        ORDER BY u.upload_date DESC 
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $uploads = $stmt->fetchAll();
    
    echo "<div style='color: green;'>✅ uploads.php query success: " . count($uploads) . " uploads</div>";
    if (!empty($uploads)) {
        echo "<pre>";
        print_r(array_slice($uploads, 0, 1));
        echo "</pre>";
    }
} catch(Exception $e) {
    echo "<div style='color: red;'>❌ uploads.php query error: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='users.php'>Test Users Page</a> | <a href='uploads.php'>Test Uploads Page</a> | <a href='index.php'>Admin Home</a></p>";
?>
