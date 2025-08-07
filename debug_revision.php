<?php
/**
 * Debug script for revision issue
 */

// Include config
require_once 'config/config.php';
require_once 'config/database.php';

// Test revision ID
$testRevisionId = '3f0cf02c-2907-40ff-8944-df6629150aca';

echo "<h1>Revision Debug Test</h1>";
echo "<p>Testing revision ID: <strong>" . htmlspecialchars($testRevisionId) . "</strong></p>";

// Test UUID validation
echo "<h2>1. UUID Validation Test</h2>";
echo "Original ID: " . htmlspecialchars($testRevisionId) . "<br>";
echo "isValidUUID result: " . (isValidUUID($testRevisionId) ? 'VALID' : 'INVALID') . "<br>";
echo "Sanitized ID: " . htmlspecialchars(sanitize($testRevisionId)) . "<br>";

// Test database connection
echo "<h2>2. Database Connection Test</h2>";
try {
    $testQuery = $pdo->query("SELECT 1 as test");
    echo "Database connection: <strong style='color: green;'>SUCCESS</strong><br>";
} catch (PDOException $e) {
    echo "Database connection: <strong style='color: red;'>FAILED</strong> - " . $e->getMessage() . "<br>";
}

// Test if revisions table exists
echo "<h2>3. Revisions Table Test</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'revisions'");
    $tableExists = $stmt->rowCount() > 0;
    echo "Revisions table exists: " . ($tableExists ? '<strong style="color: green;">YES</strong>' : '<strong style="color: red;">NO</strong>') . "<br>";
    
    if ($tableExists) {
        // Get table structure
        $stmt = $pdo->query("DESCRIBE revisions");
        echo "<h4>Table Structure:</h4>";
        echo "<ul>";
        while ($column = $stmt->fetch()) {
            echo "<li>" . $column['Field'] . " - " . $column['Type'] . "</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "Table check error: " . $e->getMessage() . "<br>";
}

// Test if specific revision exists
echo "<h2>4. Specific Revision Test</h2>";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM revisions WHERE id = ?");
    $stmt->execute([$testRevisionId]);
    $count = $stmt->fetchColumn();
    
    echo "Revision exists in database: " . ($count > 0 ? '<strong style="color: green;">YES</strong>' : '<strong style="color: red;">NO</strong>') . "<br>";
    echo "Count: " . $count . "<br>";
    
    if ($count > 0) {
        // Get the revision details
        $stmt = $pdo->prepare("SELECT * FROM revisions WHERE id = ?");
        $stmt->execute([$testRevisionId]);
        $revision = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h4>Revision Details:</h4>";
        echo "<pre>";
        print_r($revision);
        echo "</pre>";
    } else {
        echo "<p><strong>Revision not found!</strong> Let's check what revisions exist:</p>";
        
        // Show some existing revisions
        $stmt = $pdo->query("SELECT id, user_id, status, requested_at FROM revisions ORDER BY requested_at DESC LIMIT 5");
        echo "<h4>Recent Revisions:</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Status</th><th>Requested At</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['requested_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "Revision check error: " . $e->getMessage() . "<br>";
}

// Test session info
echo "<h2>5. Session Test</h2>";
if (session_status() == PHP_SESSION_ACTIVE) {
    echo "Session active: <strong style='color: green;'>YES</strong><br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "User ID in session: " . ($_SESSION['user_id'] ?? '<strong style="color: red;">NOT SET</strong>') . "<br>";
    echo "User role: " . ($_SESSION['role'] ?? '<strong style="color: red;">NOT SET</strong>') . "<br>";
    echo "Username: " . ($_SESSION['username'] ?? '<strong style="color: red;">NOT SET</strong>') . "<br>";
    echo "Is admin: " . (isset($_SESSION['is_admin']) ? ($_SESSION['is_admin'] ? 'YES' : 'NO') : '<strong style="color: red;">NOT SET</strong>') . "<br>";
    
    echo "<h4>All Session Data:</h4>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "Session active: <strong style='color: red;'>NO</strong><br>";
    echo "<p><strong>Session başlatılmamış! Bu revision-detail.php'nin çalışmamasının ana sebebi.</strong></p>";
}

// Test file permissions
echo "<h2>6. File Permissions Test</h2>";
$revisionDetailPath = __DIR__ . '/user/revision-detail.php';
echo "revision-detail.php exists: " . (file_exists($revisionDetailPath) ? '<strong style="color: green;">YES</strong>' : '<strong style="color: red;">NO</strong>') . "<br>";
echo "revision-detail.php readable: " . (is_readable($revisionDetailPath) ? '<strong style="color: green;">YES</strong>' : '<strong style="color: red;">NO</strong>') . "<br>";

echo "<hr>";
echo "<h2>7. Direct Test Links</h2>";
echo "<p><strong>Test Links:</strong></p>";
echo "<ul>";
echo "<li><a href='user/revisions.php' target='_blank'>Revisions List Page</a></li>";
echo "<li><a href='user/revision-detail.php?id=" . $testRevisionId . "' target='_blank'>Direct Revision Detail Page</a></li>";
echo "<li><a href='user/revision-detail.php?id=" . $testRevisionId . "' target='_blank'>Test This Specific Revision</a></li>";
echo "</ul>";
echo "<p><em>Debug mode bypass aktif olduğu için bu linkler çalışmalı.</em></p>";

echo "<hr>";
echo "<p><a href='user/revisions.php'>Back to Revisions</a></p>";
echo "<p><em>Debug completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>