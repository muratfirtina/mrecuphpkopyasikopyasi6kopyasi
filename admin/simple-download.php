<?php
/**
 * Simple Download Script - Session bypass test
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_GET['file'])) {
    die('No file specified');
}

$filename = $_GET['file'];
$fullPath = $_SERVER['DOCUMENT_ROOT'] . '<?php echo BASE_URL; ?>/uploads/user_files/' . $filename;

echo "<h3>Simple Download Test</h3>";
echo "Requested file: " . htmlspecialchars($filename) . "<br>";
echo "Full path: " . $fullPath . "<br>";
echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "<br>";

if (file_exists($fullPath)) {
    echo "File size: " . filesize($fullPath) . " bytes<br>";
    echo "MIME type: " . mime_content_type($fullPath) . "<br>";
    
    echo "<br><a href='?file=" . urlencode($filename) . "&download=1' style='background: green; color: white; padding: 10px; text-decoration: none;'>⬇️ Force Download</a><br>";
    
    if (isset($_GET['download'])) {
        // Force download
        $originalName = 'downloaded_' . $filename;
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $originalName . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        
        readfile($fullPath);
        exit;
    }
} else {
    echo "❌ File not found<br>";
    
    // List directory contents
    $dir = dirname($fullPath);
    if (is_dir($dir)) {
        echo "<br>Directory contents:<br>";
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "- " . $file . "<br>";
            }
        }
    }
}
?>
