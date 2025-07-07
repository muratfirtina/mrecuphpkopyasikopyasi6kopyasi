<?php
/**
 * Download Debug Version - Hata ayıklama için
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Gerekli sınıfları ve fonksiyonları include et
if (!function_exists('isValidUUID')) {
    require_once '../includes/functions.php';
}
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

echo "<h2>Download Debug Mode</h2>";
echo "<p>Debugging download request...</p>";

// Parametreleri kontrol et
echo "<h3>1. Parameters</h3>";
echo "GET parameters: " . print_r($_GET, true) . "<br>";

if (!isset($_GET['type']) && !isset($_GET['file_id'])) {
    die('<p style="color: red;">❌ Missing parameters: type or file_id required</p>');
}

$type = $_GET['type'] ?? 'response';
$uploadId = $_GET['id'] ?? null;
$fileId = $_GET['file_id'] ?? null;

echo "Type: $type<br>";
echo "Upload ID: " . ($uploadId ?: 'NULL') . "<br>";
echo "File ID: " . ($fileId ?: 'NULL') . "<br>";

// Admin kontrolü
echo "<h3>2. Authentication Check</h3>";
echo "Session status: " . session_status() . "<br>";
echo "User ID in session: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "Role in session: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('<p style="color: red;">❌ Authentication failed - Not admin</p>');
}

echo "✅ Authentication passed<br>";

// File processing
echo "<h3>3. File Processing</h3>";

$fullPath = '';
$fileName = '';

try {
    if ($uploadId && $type === 'original') {
        echo "Processing original file download...<br>";
        
        if (!isValidUUID($uploadId)) {
            die('<p style="color: red;">❌ Invalid UUID format</p>');
        }
        
        $fileManager = new FileManager($pdo);
        $upload = $fileManager->getUploadById($uploadId);
        
        if (!$upload) {
            die('<p style="color: red;">❌ Upload not found in database</p>');
        }
        
        echo "Upload found: " . htmlspecialchars($upload['original_name']) . "<br>";
        
        if (empty($upload['filename'])) {
            die('<p style="color: red;">❌ No filename in database record</p>');
        }
        
        echo "Database filename: " . htmlspecialchars($upload['filename']) . "<br>";
        
        // Path oluştur
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . $upload['filename'];
        $fileName = $upload['original_name'];
        
        echo "Full path: $fullPath<br>";
        echo "Download filename: " . htmlspecialchars($fileName) . "<br>";
        
    } else {
        die('<p style="color: red;">❌ Invalid request type or missing parameters</p>');
    }
    
    // Dosya kontrolü
    echo "<h3>4. File System Check</h3>";
    echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "<br>";
    
    if (!file_exists($fullPath)) {
        echo "<p style='color: red;'>❌ File not found on disk</p>";
        
        // Directory içeriği
        $uploadDir = dirname($fullPath);
        if (is_dir($uploadDir)) {
            $files = array_diff(scandir($uploadDir), ['.', '..']);
            echo "Directory contents: " . implode(', ', $files) . "<br>";
        }
        
        die();
    }
    
    $fileSize = filesize($fullPath);
    echo "File size: $fileSize bytes<br>";
    echo "File readable: " . (is_readable($fullPath) ? 'YES' : 'NO') . "<br>";
    
    if (!is_readable($fullPath)) {
        die('<p style="color: red;">❌ File is not readable</p>');
    }
    
    echo "✅ All checks passed - File ready for download<br>";
    
    // Test download with debug info
    echo "<h3>5. Download Test</h3>";
    echo "<p>Click below to attempt actual download:</p>";
    echo "<a href='download.php?type=original&id=$uploadId' style='background: #007bff; color: white; padding: 10px; text-decoration: none;'>🔽 Attempt Download</a><br><br>";
    
    echo "<h3>6. Headers Debug</h3>";
    echo "<p>Headers that would be sent:</p>";
    echo "<pre>";
    echo "Content-Type: application/octet-stream\n";
    echo "Content-Disposition: attachment; filename=\"" . addslashes($fileName) . "\"\n";
    echo "Content-Length: $fileSize\n";
    echo "Cache-Control: no-cache, must-revalidate\n";
    echo "Expires: 0\n";
    echo "Pragma: public\n";
    echo "</pre>";
    
    echo "<h3>7. Manual Download Link</h3>";
    echo "<p>Direct file access (if permissions allow):</p>";
    $relativeFileUrl = "/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/" . $upload['filename'];
    echo "<a href='$relativeFileUrl' target='_blank'>Direct File Link</a><br>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<a href='uploads.php'>← Back to Uploads</a>";
?>
