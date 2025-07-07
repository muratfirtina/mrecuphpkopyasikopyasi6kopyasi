<?php
/**
 * Download Test - Safe debug version
 */

// Headers'ı kontrol et
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Download Process Debug</h2>";

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
    echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NULL') . "<br>";
    echo "Session role: " . ($_SESSION['role'] ?? 'NULL') . "<br>";
    exit;
}

echo "✅ Admin access OK<br>";

// Parametreleri kontrol et
$type = $_GET['type'] ?? 'original';
$uploadId = $_GET['id'] ?? '5c308aa4-770a-4db3-b361-97bcc696dde2'; // Varsayılan test ID
$fileId = $_GET['file_id'] ?? null;

echo "Parameters:<br>";
echo "- Type: $type<br>";
echo "- Upload ID: " . ($uploadId ?: 'NULL') . "<br>";
echo "- File ID: " . ($fileId ?: 'NULL') . "<br>";

if (!$uploadId && !$fileId) {
    echo "❌ Missing parameters<br>";
    echo "<h3>Test Links:</h3>";
    echo "<a href='?type=original&id=5c308aa4-770a-4db3-b361-97bcc696dde2'>Test Original Download</a><br>";
    echo "<a href='uploads.php'>Go to Uploads Page</a><br>";
    exit;
}

$fullPath = '';
$fileName = '';

try {
    if ($uploadId && $type === 'original') {
        echo "<h3>Processing original file download...</h3>";
        
        if (!isValidUUID($uploadId)) {
            echo "❌ Invalid UUID format: $uploadId<br>";
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
            echo "Upload data: <pre>" . print_r($upload, true) . "</pre>";
            exit;
        }
        echo "✅ Filename: " . htmlspecialchars($upload['filename']) . "<br>";
        
        // Path oluştur
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . $upload['filename'];
        $fileName = $upload['original_name'];
        
        echo "Full path: $fullPath<br>";
        echo "File exists: " . (file_exists($fullPath) ? "✅ YES" : "❌ NO") . "<br>";
        
        if (file_exists($fullPath)) {
            echo "File size: " . filesize($fullPath) . " bytes<br>";
            echo "Is readable: " . (is_readable($fullPath) ? "✅ YES" : "❌ NO") . "<br>";
        }
        
    } else {
        echo "❌ Unsupported download type or parameters<br>";
        exit;
    }
    
    // Dosya varlığını kontrol et
    if (!file_exists($fullPath)) {
        echo "❌ File not found on disk<br>";
        echo "Looking for: $fullPath<br>";
        
        // Alternatif konumları kontrol et
        $altPaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . $upload['filename'],
            __DIR__ . '/../uploads/user_files/' . $upload['filename'],
            __DIR__ . '/../uploads/' . $upload['filename']
        ];
        
        echo "Alternative paths checked:<br>";
        foreach ($altPaths as $altPath) {
            echo "- $altPath: " . (file_exists($altPath) ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";
        }
        exit;
    }
    
    echo "✅ All checks passed!<br>";
    echo "<h3>Download would work with these settings:</h3>";
    echo "- File path: $fullPath<br>";
    echo "- Download name: $fileName<br>";
    echo "- File size: " . filesize($fullPath) . " bytes<br>";
    
    echo "<h3>Test Download</h3>";
    echo "<a href='download.php?type=original&id=$uploadId' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Real Download</a><br>";
    
    echo "<h3>Force Download (Debug)</h3>";
    echo "<a href='?type=original&id=$uploadId&force=1' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Force Download</a><br>";
    
    // Force download test
    if (isset($_GET['force']) && $_GET['force'] == '1') {
        echo "<script>console.log('Starting force download...');</script>";
        
        // Clear any output
        ob_end_clean();
        
        // Send download headers
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        header('Pragma: public');
        
        // Output file
        readfile($fullPath);
        exit;
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}

echo "<hr>";
echo "<a href='uploads.php'>← Back to Uploads</a>";
?>
