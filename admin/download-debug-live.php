<?php
/**
 * Download Debug - Exact işlem takibi
 */

// Debug mode - Her şeyi göster
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DOWNLOAD DEBUG START ===<br>";
echo "Time: " . date('Y-m-d H:i:s') . "<br>";
echo "URL: " . $_SERVER['REQUEST_URI'] . "<br>";

require_once '../config/config.php';
require_once '../config/database.php';

// Gerekli sınıfları ve fonksiyonları include et
if (!function_exists('isValidUUID')) {
    require_once '../includes/functions.php';
}
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

echo "✅ Includes loaded<br>";

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "❌ Admin access denied<br>";
    exit;
}

echo "✅ Admin access OK<br>";

// Parametreleri kontrol et
$type = $_GET['type'] ?? 'response';
$uploadId = $_GET['id'] ?? null;
$fileId = $_GET['file_id'] ?? null;

echo "Parameters:<br>";
echo "- Type: $type<br>";
echo "- Upload ID: " . ($uploadId ?: 'NULL') . "<br>";
echo "- File ID: " . ($fileId ?: 'NULL') . "<br>";

if (!$uploadId && !$fileId) {
    echo "❌ Missing parameters<br>";
    exit;
}

$fullPath = '';
$fileName = '';

try {
    if ($uploadId && $type === 'original') {
        echo "<br>🔍 Processing original file download...<br>";
        
        if (!isValidUUID($uploadId)) {
            echo "❌ Invalid UUID format<br>";
            exit;
        }
        echo "✅ Valid UUID<br>";
        
        $fileManager = new FileManager($pdo);
        $upload = $fileManager->getUploadById($uploadId);
        
        if (!$upload) {
            echo "❌ Upload not found in database<br>";
            exit;
        }
        echo "✅ Upload found: " . htmlspecialchars($upload['original_name']) . "<br>";
        
        if (empty($upload['filename'])) {
            echo "❌ No filename in database<br>";
            exit;
        }
        echo "✅ Filename from DB: " . htmlspecialchars($upload['filename']) . "<br>";
        
        // Smart path detection
        echo "<br>📁 Testing file paths:<br>";
        $possiblePaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . $upload['filename'],
            $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . basename($upload['filename']),
            $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . $upload['filename'],
            $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . basename($upload['filename']),
        ];
        
        foreach ($possiblePaths as $index => $path) {
            echo "Path " . ($index + 1) . ": " . $path . "<br>";
            if (file_exists($path)) {
                echo "✅ EXISTS (" . formatFileSize(filesize($path)) . ")<br>";
                $fullPath = $path;
                break;
            } else {
                echo "❌ NOT FOUND<br>";
            }
        }
        
        if (!$fullPath) {
            echo "<br>❌ File not found in any location<br>";
            echo "<br>📂 Let's check what's actually in the upload directory:<br>";
            
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/';
            if (is_dir($uploadDir)) {
                $files = scandir($uploadDir);
                echo "Files in user_files:<br>";
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..') {
                        $filePath = $uploadDir . $file;
                        $size = is_file($filePath) ? filesize($filePath) : 0;
                        echo "- $file (" . formatFileSize($size) . ")<br>";
                    }
                }
            }
            
            // Recursive search
            echo "<br>🔍 Recursive search for the file:<br>";
            $searchName = basename($upload['filename']);
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/')
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getFilename() === $searchName) {
                    echo "✅ FOUND: " . $file->getPathname() . "<br>";
                    $fullPath = $file->getPathname();
                    break;
                }
            }
            
            if (!$fullPath) {
                echo "❌ File not found anywhere<br>";
                exit;
            }
        }
        
        $fileName = $upload['original_name'];
        
        echo "<br>✅ Final settings:<br>";
        echo "- File path: $fullPath<br>";
        echo "- Download name: $fileName<br>";
        echo "- File size: " . formatFileSize(filesize($fullPath)) . "<br>";
        echo "- Is readable: " . (is_readable($fullPath) ? "YES" : "NO") . "<br>";
        
        echo "<br>🚀 Starting download process...<br>";
        
        // Bu noktada gerçek download yapalım
        if (isset($_GET['debug']) && $_GET['debug'] == '0') {
            echo "<br>📤 Attempting real download...<br>";
            
            // Clear output buffer
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set headers
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
            header('Content-Length: ' . filesize($fullPath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: 0');
            header('Pragma: public');
            
            // Send file
            readfile($fullPath);
            exit;
        } else {
            echo "<br>🔧 Debug mode active. Add &debug=0 to URL for real download.<br>";
            echo "<a href='?type=original&id=$uploadId&debug=0' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Real Download</a><br>";
        }
        
    } else {
        echo "❌ Unsupported download type or parameters<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<br>=== DEBUG END ===<br>";
echo "<a href='uploads.php'>← Back to Uploads</a>";
?>
