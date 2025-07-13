<?php
/**
 * FileManager Methods Debug
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>FileManager Methods Debug</h2>";

try {
    require_once 'config/config.php';
    require_once 'config/database.php';
    require_once 'includes/User.php';
    require_once 'includes/FileManager.php';
    
    if (!$pdo) {
        echo "❌ Database connection failed<br>";
        exit;
    }
    
    echo "✅ Database connected successfully<br><br>";
    
    $fileManager = new FileManager($pdo);
    
    // 1. Debug getAllUploads step by step
    echo "<h3>1. Debug getAllUploads Method</h3>";
    
    echo "<strong>Step 1: Test Parameters</strong><br>";
    $page = 1;
    $limit = 20;
    $status = '';
    $search = '';
    
    $offset = ($page - 1) * $limit;
    echo "Parameters: page=$page, limit=$limit, offset=$offset, status='$status', search='$search'<br>";
    
    echo "<strong>Step 2: Build WHERE clause</strong><br>";
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($status) {
        $whereClause .= " AND fu.status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $whereClause .= " AND (fu.original_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR b.name LIKE ? OR m.name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    echo "WHERE clause: $whereClause<br>";
    echo "Parameters: " . print_r($params, true) . "<br>";
    
    echo "<strong>Step 3: Build complete SQL</strong><br>";
    $sql = "
        SELECT fu.*, u.username, u.email, u.first_name, u.last_name,
               b.name as brand_name, m.name as model_name
        FROM file_uploads fu
        LEFT JOIN users u ON fu.user_id = u.id
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        $whereClause
        ORDER BY fu.upload_date DESC
        LIMIT ? OFFSET ?
    ";
    
    echo "Complete SQL: <pre>$sql</pre>";
    
    $params[] = $limit;
    $params[] = $offset;
    echo "Final parameters: " . print_r($params, true) . "<br>";
    
    echo "<strong>Step 4: Execute manually</strong><br>";
    try {
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        echo "Execute result: " . ($result ? 'SUCCESS' : 'FAILED') . "<br>";
        
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Manual execution result count: " . count($files) . "<br>";
        
        if (!empty($files)) {
            echo "First record ID: " . $files[0]['id'] . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ Manual execution error: " . $e->getMessage() . "<br>";
    }
    
    echo "<strong>Step 5: Test via FileManager method</strong><br>";
    try {
        $methodResult = $fileManager->getAllUploads($page, $limit, $status, $search);
        echo "FileManager method result count: " . count($methodResult) . "<br>";
        
        if (empty($methodResult)) {
            echo "❌ Method returned empty! Let's check the source code...<br>";
        }
    } catch (Exception $e) {
        echo "❌ FileManager method error: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
    
    // 2. Debug getAllRevisions step by step
    echo "<h3>2. Debug getAllRevisions Method</h3>";
    
    echo "<strong>Step 1: Test Parameters</strong><br>";
    $page = 1;
    $limit = 20;
    $status = '';
    $dateFrom = '';
    $dateTo = '';
    $search = '';
    
    $offset = ($page - 1) * $limit;
    echo "Parameters: page=$page, limit=$limit, offset=$offset<br>";
    
    echo "<strong>Step 2: Build WHERE clause</strong><br>";
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if ($status) {
        $whereClause .= " AND r.status = ?";
        $params[] = $status;
    }
    
    if ($dateFrom) {
        $whereClause .= " AND DATE(r.requested_at) >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereClause .= " AND DATE(r.requested_at) <= ?";
        $params[] = $dateTo;
    }
    
    if ($search) {
        $whereClause .= " AND (r.request_notes LIKE ? OR fu.original_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR b.name LIKE ? OR m.name LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    echo "WHERE clause: $whereClause<br>";
    echo "Parameters: " . print_r($params, true) . "<br>";
    
    echo "<strong>Step 3: Build complete SQL</strong><br>";
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
        $whereClause
        ORDER BY r.requested_at DESC
        LIMIT ? OFFSET ?
    ";
    
    echo "Complete SQL: <pre>$sql</pre>";
    
    $params[] = $limit;
    $params[] = $offset;
    echo "Final parameters: " . print_r($params, true) . "<br>";
    
    echo "<strong>Step 4: Execute manually</strong><br>";
    try {
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        echo "Execute result: " . ($result ? 'SUCCESS' : 'FAILED') . "<br>";
        
        $revisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Manual execution result count: " . count($revisions) . "<br>";
        
        if (!empty($revisions)) {
            echo "First record ID: " . $revisions[0]['id'] . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ Manual execution error: " . $e->getMessage() . "<br>";
    }
    
    echo "<strong>Step 5: Test via FileManager method</strong><br>";
    try {
        $methodResult = $fileManager->getAllRevisions($page, $limit, $status, $dateFrom, $dateTo, $search);
        echo "FileManager method result count: " . count($methodResult) . "<br>";
        
        if (empty($methodResult)) {
            echo "❌ Method returned empty! Let's check the source code...<br>";
        }
    } catch (Exception $e) {
        echo "❌ FileManager method error: " . $e->getMessage() . "<br>";
    }
    
    // 3. Let's look at the actual FileManager source
    echo "<hr>";
    echo "<h3>3. FileManager Source Code Analysis</h3>";
    
    $fileManagerPath = 'includes/FileManager.php';
    if (file_exists($fileManagerPath)) {
        $content = file_get_contents($fileManagerPath);
        
        // Find getAllUploads method
        if (preg_match('/public function getAllUploads\([^}]+\}/s', $content, $matches)) {
            echo "<strong>getAllUploads method found:</strong><br>";
            echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
        } else {
            echo "❌ getAllUploads method not found in source!<br>";
        }
        
        // Find getAllRevisions method
        if (preg_match('/public function getAllRevisions\([^}]+\}/s', $content, $matches)) {
            echo "<strong>getAllRevisions method found:</strong><br>";
            echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
        } else {
            echo "❌ getAllRevisions method not found in source!<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><strong>Debug completed!</strong>";
?>
