<?php
/**
 * SQL Debug Script
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>SQL Debug Script</h2>";

try {
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    if (!$pdo) {
        echo "❌ Database connection failed<br>";
        exit;
    }
    
    echo "✅ Database connected successfully<br><br>";
    
    // 1. Test basic file_uploads query
    echo "<h3>1. Basic file_uploads Query</h3>";
    try {
        $stmt = $pdo->query("SELECT * FROM file_uploads ORDER BY upload_date DESC LIMIT 3");
        $files = $stmt->fetchAll();
        echo "Basic query result count: " . count($files) . "<br>";
        if (!empty($files)) {
            echo "Sample record: <br>";
            echo "<pre>" . print_r($files[0], true) . "</pre>";
        }
    } catch (Exception $e) {
        echo "❌ Basic query error: " . $e->getMessage() . "<br>";
    }
    
    // 2. Test with LEFT JOINs (like in getAllUploads)
    echo "<h3>2. File uploads with JOINs (getAllUploads style)</h3>";
    try {
        $sql = "
            SELECT fu.*, u.username, u.email, u.first_name, u.last_name,
                   b.name as brand_name, m.name as model_name
            FROM file_uploads fu
            LEFT JOIN users u ON fu.user_id = u.id
            LEFT JOIN brands b ON fu.brand_id = b.id
            LEFT JOIN models m ON fu.model_id = m.id
            WHERE 1=1
            ORDER BY fu.upload_date DESC
            LIMIT 5 OFFSET 0
        ";
        
        echo "SQL Query: <pre>$sql</pre>";
        
        $stmt = $pdo->query($sql);
        $files = $stmt->fetchAll();
        echo "JOIN query result count: " . count($files) . "<br>";
        if (!empty($files)) {
            echo "Sample record: <br>";
            echo "<pre>" . print_r($files[0], true) . "</pre>";
        } else {
            echo "❌ No results from JOIN query!<br>";
        }
    } catch (Exception $e) {
        echo "❌ JOIN query error: " . $e->getMessage() . "<br>";
    }
    
    // 3. Check user relationships
    echo "<h3>3. Check User Relationships</h3>";
    try {
        $stmt = $pdo->query("
            SELECT fu.id as file_id, fu.user_id, u.id as user_table_id, u.username 
            FROM file_uploads fu 
            LEFT JOIN users u ON fu.user_id = u.id 
            LIMIT 3
        ");
        $relationships = $stmt->fetchAll();
        echo "User relationship check:<br>";
        foreach ($relationships as $rel) {
            echo "File ID: {$rel['file_id']}, File User ID: {$rel['user_id']}, User Table ID: {$rel['user_table_id']}, Username: {$rel['username']}<br>";
        }
    } catch (Exception $e) {
        echo "❌ User relationship error: " . $e->getMessage() . "<br>";
    }
    
    // 4. Check brand relationships
    echo "<h3>4. Check Brand Relationships</h3>";
    try {
        $stmt = $pdo->query("
            SELECT fu.id as file_id, fu.brand_id, b.id as brand_table_id, b.name as brand_name 
            FROM file_uploads fu 
            LEFT JOIN brands b ON fu.brand_id = b.id 
            LIMIT 3
        ");
        $relationships = $stmt->fetchAll();
        echo "Brand relationship check:<br>";
        foreach ($relationships as $rel) {
            echo "File ID: {$rel['file_id']}, File Brand ID: {$rel['brand_id']}, Brand Table ID: {$rel['brand_table_id']}, Brand Name: {$rel['brand_name']}<br>";
        }
    } catch (Exception $e) {
        echo "❌ Brand relationship error: " . $e->getMessage() . "<br>";
    }
    
    // 5. Test revisions query
    echo "<h3>5. Basic revisions Query</h3>";
    try {
        $stmt = $pdo->query("SELECT * FROM revisions ORDER BY requested_at DESC LIMIT 3");
        $revisions = $stmt->fetchAll();
        echo "Basic revisions query result count: " . count($revisions) . "<br>";
        if (!empty($revisions)) {
            echo "Sample record: <br>";
            echo "<pre>" . print_r($revisions[0], true) . "</pre>";
        }
    } catch (Exception $e) {
        echo "❌ Basic revisions query error: " . $e->getMessage() . "<br>";
    }
    
    // 6. Test revisions with JOINs (like in getAllRevisions)
    echo "<h3>6. Revisions with JOINs (getAllRevisions style)</h3>";
    try {
        $sql = "
            SELECT r.*, fu.original_name, fu.filename, fu.file_size,
                   u.username, u.email, u.first_name, u.last_name,
                   b.name as brand_name, m.name as model_name,
                   fr.original_name as response_original_name
            FROM revisions r
            LEFT JOIN file_uploads fu ON r.upload_id = fu.id
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN brands b ON fu.brand_id = b.id
            LEFT JOIN models m ON fu.model_id = m.id
            LEFT JOIN file_responses fr ON r.response_id = fr.id
            WHERE 1=1
            ORDER BY r.requested_at DESC
            LIMIT 5 OFFSET 0
        ";
        
        echo "SQL Query: <pre>$sql</pre>";
        
        $stmt = $pdo->query($sql);
        $revisions = $stmt->fetchAll();
        echo "Revisions JOIN query result count: " . count($revisions) . "<br>";
        if (!empty($revisions)) {
            echo "Sample record: <br>";
            echo "<pre>" . print_r($revisions[0], true) . "</pre>";
        } else {
            echo "❌ No results from revisions JOIN query!<br>";
        }
    } catch (Exception $e) {
        echo "❌ Revisions JOIN query error: " . $e->getMessage() . "<br>";
    }
    
    // 7. Check if file_responses table exists
    echo "<h3>7. Check file_responses Table</h3>";
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'file_responses'");
        if ($stmt->rowCount() > 0) {
            echo "✅ file_responses table exists<br>";
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM file_responses");
            $count = $stmt->fetch()['count'];
            echo "📊 file_responses records: $count<br>";
        } else {
            echo "❌ file_responses table does NOT exist<br>";
            echo "This could be why revisions JOIN is failing!<br>";
        }
    } catch (Exception $e) {
        echo "❌ file_responses check error: " . $e->getMessage() . "<br>";
    }
    
    // 8. Test FileManager methods directly
    echo "<h3>8. Test FileManager Methods Directly</h3>";
    try {
        require_once 'includes/User.php';
        require_once 'includes/FileManager.php';
        
        $fileManager = new FileManager($pdo);
        
        // Test with error reporting
        echo "<strong>Testing getAllUploads method:</strong><br>";
        ob_start();
        $uploads = $fileManager->getAllUploads(1, 5);
        $output = ob_get_clean();
        if ($output) {
            echo "Method output: $output<br>";
        }
        echo "getAllUploads result count: " . count($uploads) . "<br>";
        
        echo "<strong>Testing getAllRevisions method:</strong><br>";
        ob_start();
        $revisions = $fileManager->getAllRevisions(1, 5);
        $output = ob_get_clean();
        if ($output) {
            echo "Method output: $output<br>";
        }
        echo "getAllRevisions result count: " . count($revisions) . "<br>";
        
    } catch (Exception $e) {
        echo "❌ FileManager test error: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "<br>";
}

echo "<br><strong>Debug completed!</strong>";
?>
