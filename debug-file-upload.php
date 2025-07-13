<?php
/**
 * File Upload Debug Script
 */

// Hata raporlamasını açalım
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>File Upload Debug</h1>";

try {
    // Session başlat
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    echo "<h2>1. PHP Upload Settings</h2>";
    echo "Max file size: " . ini_get('upload_max_filesize') . "<br>";
    echo "Max post size: " . ini_get('post_max_size') . "<br>";
    echo "Memory limit: " . ini_get('memory_limit') . "<br>";
    echo "Max execution time: " . ini_get('max_execution_time') . "<br>";
    echo "File uploads enabled: " . (ini_get('file_uploads') ? 'Yes' : 'No') . "<br>";
    echo "Temp directory: " . ini_get('upload_tmp_dir') . "<br>";
    
    echo "<h2>2. Request Analysis</h2>";
    echo "Request method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
    echo "Content type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "<br>";
    echo "Content length: " . ($_SERVER['CONTENT_LENGTH'] ?? 'Not set') . "<br>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo "<h3>POST Data:</h3>";
        echo "<pre>";
        print_r($_POST);
        echo "</pre>";
        
        echo "<h3>FILES Data:</h3>";
        echo "<pre>";
        print_r($_FILES);
        echo "</pre>";
        
        if (isset($_FILES['response_file'])) {
            $file = $_FILES['response_file'];
            echo "<h3>File Analysis:</h3>";
            echo "Name: " . $file['name'] . "<br>";
            echo "Type: " . $file['type'] . "<br>";
            echo "Size: " . $file['size'] . " bytes<br>";
            echo "Temp name: " . $file['tmp_name'] . "<br>";
            echo "Error: " . $file['error'] . "<br>";
            
            // Error code meanings
            $errorMessages = [
                UPLOAD_ERR_OK => 'No error',
                UPLOAD_ERR_INI_SIZE => 'File too large (php.ini)',
                UPLOAD_ERR_FORM_SIZE => 'File too large (form)',
                UPLOAD_ERR_PARTIAL => 'Partial upload',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'No temp directory',
                UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
                UPLOAD_ERR_EXTENSION => 'Extension stopped upload'
            ];
            
            echo "Error meaning: " . ($errorMessages[$file['error']] ?? 'Unknown error') . "<br>";
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                echo "File exists in temp: " . (file_exists($file['tmp_name']) ? 'Yes' : 'No') . "<br>";
                echo "Is uploaded file: " . (is_uploaded_file($file['tmp_name']) ? 'Yes' : 'No') . "<br>";
            }
        }
    }
    
    echo "<h2>3. Directory Checks</h2>";
    
    require_once 'config/config.php';
    
    $uploadDir = UPLOAD_PATH . 'response_files/';
    echo "Upload directory: " . $uploadDir . "<br>";
    echo "Directory exists: " . (is_dir($uploadDir) ? 'Yes' : 'No') . "<br>";
    echo "Directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "<br>";
    
    if (!is_dir($uploadDir)) {
        echo "Attempting to create directory...<br>";
        if (mkdir($uploadDir, 0755, true)) {
            echo "✅ Directory created successfully<br>";
        } else {
            echo "❌ Failed to create directory<br>";
        }
    }
    
    echo "<h2>4. Database Connection</h2>";
    require_once 'config/database.php';
    
    if (isset($pdo) && $pdo) {
        echo "✅ Database connection OK<br>";
        
        // Test file_responses table
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'file_responses'");
            if ($stmt->rowCount() > 0) {
                echo "✅ file_responses table exists<br>";
                
                // Check table structure
                $columns = $pdo->query("DESCRIBE file_responses")->fetchAll();
                echo "Table columns: ";
                foreach ($columns as $col) {
                    echo $col['Field'] . " ";
                }
                echo "<br>";
            } else {
                echo "❌ file_responses table missing<br>";
            }
        } catch (Exception $e) {
            echo "❌ Database table error: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Database connection failed<br>";
    }
    
    echo "<h2>5. FileManager Test</h2>";
    require_once 'includes/FileManager.php';
    
    $fileManager = new FileManager($pdo);
    echo "✅ FileManager instance created<br>";
    
    if (method_exists($fileManager, 'uploadResponseFile')) {
        echo "✅ uploadResponseFile method exists<br>";
    } else {
        echo "❌ uploadResponseFile method missing<br>";
    }
    
    echo "<h2>6. Session Info</h2>";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
    echo "Is admin: " . (isset($_SESSION['is_admin']) ? ($_SESSION['is_admin'] ? 'Yes' : 'No') : 'Not set') . "<br>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['response_file'])) {
        echo "<h2>7. Upload Test</h2>";
        
        $uploadId = $_POST['upload_id'] ?? 'test-upload-id';
        $creditsCharged = floatval($_POST['credits_charged'] ?? 0);
        $responseNotes = $_POST['response_notes'] ?? 'Test upload';
        
        echo "Upload ID: " . $uploadId . "<br>";
        echo "Credits: " . $creditsCharged . "<br>";
        echo "Notes: " . $responseNotes . "<br>";
        
        if (isset($_SESSION['user_id'])) {
            try {
                $result = $fileManager->uploadResponseFile($uploadId, $_FILES['response_file'], $creditsCharged, $responseNotes);
                
                echo "<h3>Upload Result:</h3>";
                echo "<pre>";
                print_r($result);
                echo "</pre>";
                
            } catch (Exception $e) {
                echo "<h3>Upload Error:</h3>";
                echo "Error: " . $e->getMessage() . "<br>";
                echo "File: " . $e->getFile() . "<br>";
                echo "Line: " . $e->getLine() . "<br>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
            }
        } else {
            echo "❌ No user session for upload test<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Fatal Error:</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

?>

<!-- Test Upload Form -->
<h2>Upload Test Form</h2>
<form method="POST" enctype="multipart/form-data">
    <div>
        <label>Upload ID:</label>
        <input type="text" name="upload_id" value="7d227059-7f00-4409-a849-5d766ec9136d">
    </div>
    <div>
        <label>File:</label>
        <input type="file" name="response_file" required>
    </div>
    <div>
        <label>Credits:</label>
        <input type="number" name="credits_charged" value="0" step="0.01">
    </div>
    <div>
        <label>Notes:</label>
        <textarea name="response_notes">Test upload from debug script</textarea>
    </div>
    <div>
        <button type="submit">Test Upload</button>
    </div>
</form>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2, h3 { color: #333; }
    pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    form div { margin: 10px 0; }
    label { display: inline-block; width: 100px; }
    input, textarea { width: 200px; padding: 5px; }
    button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; }
</style>
