<?php
/**
 * Database Structure and Data Check
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Database Structure and Data Check</h2>";

try {
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    if (!$pdo) {
        echo "❌ Database connection failed<br>";
        exit;
    }
    
    echo "✅ Database connected successfully<br><br>";
    
    // 1. Check table existence
    echo "<h3>1. Table Existence Check</h3>";
    $tables = ['file_uploads', 'revisions', 'users', 'brands', 'models'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    }
    
    echo "<br>";
    
    // 2. Check data counts
    echo "<h3>2. Data Count Check</h3>";
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch()['count'];
            echo "📊 Table '$table': $count records<br>";
        } catch (Exception $e) {
            echo "❌ Error checking '$table': " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br>";
    
    // 3. Check file_uploads structure
    echo "<h3>3. file_uploads Table Structure</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE file_uploads");
        $columns = $stmt->fetchAll();
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "❌ Error checking file_uploads structure: " . $e->getMessage() . "<br>";
    }
    
    echo "<br>";
    
    // 4. Check revisions structure
    echo "<h3>4. revisions Table Structure</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE revisions");
        $columns = $stmt->fetchAll();
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "❌ Error checking revisions structure: " . $e->getMessage() . "<br>";
    }
    
    echo "<br>";
    
    // 5. Sample data from file_uploads
    echo "<h3>5. Sample file_uploads Data</h3>";
    try {
        $stmt = $pdo->query("SELECT id, user_id, original_name, status, upload_date FROM file_uploads ORDER BY upload_date DESC LIMIT 5");
        $files = $stmt->fetchAll();
        if (empty($files)) {
            echo "❌ No files found in file_uploads table<br>";
        } else {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Original Name</th><th>Status</th><th>Upload Date</th></tr>";
            foreach ($files as $file) {
                echo "<tr>";
                echo "<td>{$file['id']}</td>";
                echo "<td>{$file['user_id']}</td>";
                echo "<td>{$file['original_name']}</td>";
                echo "<td>{$file['status']}</td>";
                echo "<td>{$file['upload_date']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "❌ Error checking file_uploads data: " . $e->getMessage() . "<br>";
    }
    
    echo "<br>";
    
    // 6. Sample data from revisions
    echo "<h3>6. Sample revisions Data</h3>";
    try {
        $stmt = $pdo->query("SELECT id, user_id, upload_id, status, requested_at FROM revisions ORDER BY requested_at DESC LIMIT 5");
        $revisions = $stmt->fetchAll();
        if (empty($revisions)) {
            echo "❌ No revisions found in revisions table<br>";
        } else {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Upload ID</th><th>Status</th><th>Requested At</th></tr>";
            foreach ($revisions as $revision) {
                echo "<tr>";
                echo "<td>{$revision['id']}</td>";
                echo "<td>{$revision['user_id']}</td>";
                echo "<td>{$revision['upload_id']}</td>";
                echo "<td>{$revision['status']}</td>";
                echo "<td>{$revision['requested_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "❌ Error checking revisions data: " . $e->getMessage() . "<br>";
    }
    
    echo "<br>";
    
    // 7. Test FileManager methods
    echo "<h3>7. FileManager Methods Test</h3>";
    try {
        require_once 'includes/User.php';
        require_once 'includes/FileManager.php';
        
        $fileManager = new FileManager($pdo);
        
        // Test getAllUploads
        echo "<strong>Testing getAllUploads():</strong><br>";
        $uploads = $fileManager->getAllUploads(1, 5);
        echo "Result count: " . count($uploads) . "<br>";
        if (!empty($uploads)) {
            echo "First result: " . print_r($uploads[0], true) . "<br>";
        }
        
        echo "<br>";
        
        // Test getAllRevisions
        echo "<strong>Testing getAllRevisions():</strong><br>";
        $revisions = $fileManager->getAllRevisions(1, 5);
        echo "Result count: " . count($revisions) . "<br>";
        if (!empty($revisions)) {
            echo "First result: " . print_r($revisions[0], true) . "<br>";
        }
        
        echo "<br>";
        
        // Test getFileStats
        echo "<strong>Testing getFileStats():</strong><br>";
        $stats = $fileManager->getFileStats();
        echo "Stats: " . print_r($stats, true) . "<br>";
        
    } catch (Exception $e) {
        echo "❌ Error testing FileManager: " . $e->getMessage() . "<br>";
    }
    
    echo "<br>";
    
    // 8. Check for missing FK relationships
    echo "<h3>8. Foreign Key Relationship Check</h3>";
    try {
        // Check for orphaned records
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM file_uploads fu 
            LEFT JOIN users u ON fu.user_id = u.id 
            WHERE u.id IS NULL
        ");
        $orphanedFiles = $stmt->fetch()['count'];
        echo "Orphaned files (no user): $orphanedFiles<br>";
        
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM revisions r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE u.id IS NULL
        ");
        $orphanedRevisions = $stmt->fetch()['count'];
        echo "Orphaned revisions (no user): $orphanedRevisions<br>";
        
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM revisions r 
            LEFT JOIN file_uploads fu ON r.upload_id = fu.id 
            WHERE fu.id IS NULL
        ");
        $orphanedRevisions2 = $stmt->fetch()['count'];
        echo "Orphaned revisions (no file): $orphanedRevisions2<br>";
        
    } catch (Exception $e) {
        echo "❌ Error checking FK relationships: " . $e->getMessage() . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "<br>";
}

echo "<br><strong>Check completed!</strong>";
?>
