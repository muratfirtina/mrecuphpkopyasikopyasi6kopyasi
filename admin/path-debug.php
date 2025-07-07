<?php
/**
 * File Path Debug - Dosya yolu problemini çöz
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Gerekli sınıfları ve fonksiyonları include et
if (!function_exists('isValidUUID')) {
    require_once '../includes/functions.php';
}
require_once '../includes/FileManager.php';
require_once '../includes/User.php';

echo "<h2>🔍 File Path Debug & Fix</h2>";

$uploadId = '5c308aa4-770a-4db3-b361-97bcc696dde2';

// Database'den dosya bilgisini al
$fileManager = new FileManager($pdo);
$upload = $fileManager->getUploadById($uploadId);

if ($upload) {
    echo "<h3>📊 Database Info:</h3>";
    echo "Filename in DB: " . htmlspecialchars($upload['filename']) . "<br>";
    echo "Original name: " . htmlspecialchars($upload['original_name']) . "<br>";
    
    echo "<h3>📁 Path Testing:</h3>";
    
    // Farklı olası path'leri test et
    $possiblePaths = [
        // Path 1: Tam path (database'deki gibi)
        $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . $upload['filename'],
        
        // Path 2: Sadece filename (path olmadan)
        $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . basename($upload['filename']),
        
        // Path 3: user_files altında direkt
        $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . $upload['filename'],
        
        // Path 4: Ana uploads altında
        $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/' . basename($upload['filename']),
    ];
    
    $foundPath = null;
    
    foreach ($possiblePaths as $index => $path) {
        echo "<strong>Path " . ($index + 1) . ":</strong> " . $path . "<br>";
        echo "Exists: " . (file_exists($path) ? "✅ YES" : "❌ NO");
        if (file_exists($path)) {
            echo " (" . formatFileSize(filesize($path)) . ")";
            $foundPath = $path;
        }
        echo "<br><br>";
    }
    
    // Fiziksel dosyaları ara
    echo "<h3>🔍 Physical File Search:</h3>";
    
    $searchDirs = [
        $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/',
        $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/',
    ];
    
    foreach ($searchDirs as $searchDir) {
        echo "<strong>Searching in:</strong> $searchDir<br>";
        if (is_dir($searchDir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($searchDir));
            $foundFiles = [];
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $filename = $file->getFilename();
                    $size = $file->getSize();
                    $path = $file->getPathname();
                    
                    // 1MB'dan büyük dosyaları göster (gerçek upload dosyaları)
                    if ($size > 1000000) {
                        $foundFiles[] = [
                            'path' => $path,
                            'filename' => $filename,
                            'size' => $size
                        ];
                    }
                }
            }
            
            if ($foundFiles) {
                echo "<ul>";
                foreach ($foundFiles as $file) {
                    echo "<li>" . $file['filename'] . " (" . formatFileSize($file['size']) . ")<br>";
                    echo "<small>Path: " . $file['path'] . "</small></li>";
                }
                echo "</ul>";
            } else {
                echo "No large files found<br>";
            }
        } else {
            echo "Directory does not exist<br>";
        }
        echo "<br>";
    }
    
    // Çözüm önerisi
    echo "<h3>🔧 Solution:</h3>";
    
    if ($foundPath) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "✅ <strong>File found at:</strong><br>";
        echo $foundPath . "<br><br>";
        echo "<strong>Working download link:</strong><br>";
        echo "<a href='download-fixed.php?id=$uploadId&path=" . urlencode($foundPath) . "' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Download Fixed Version</a>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "❌ <strong>File not found in any expected location</strong><br>";
        echo "The file may have been moved or deleted.";
        echo "</div>";
    }
    
} else {
    echo "❌ Upload not found in database";
}

echo "<hr>";
echo "<a href='uploads.php'>← Back to Uploads</a>";
?>
