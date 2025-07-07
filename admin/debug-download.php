<?php
/**
 * Download Debug Script
 * Download sorunlarını test etmek için debug script
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Test parametreleri
$uploadId = '5c308aa4-770a-4db3-b361-97bcc696dde2';

echo "<h2>Download Debug Script</h2>";

// 1. Function exists kontrolü
echo "<h3>1. Fonksiyon Kontrolü</h3>";
echo "isValidUUID function exists: " . (function_exists('isValidUUID') ? 'YES' : 'NO') . "<br>";
echo "formatFileSize function exists: " . (function_exists('formatFileSize') ? 'YES' : 'NO') . "<br>";

// 2. UUID doğrulama testi
echo "<h3>2. UUID Doğrulama</h3>";
if (function_exists('isValidUUID')) {
    echo "UUID ($uploadId) valid: " . (isValidUUID($uploadId) ? 'YES' : 'NO') . "<br>";
} else {
    echo "isValidUUID function not found!<br>";
}

// 3. FileManager class kontrolü
echo "<h3>3. FileManager Kontrolü</h3>";
if (class_exists('FileManager')) {
    echo "FileManager class exists: YES<br>";
    $fileManager = new FileManager($pdo);
    echo "FileManager instance created: YES<br>";
    
    // Upload bilgisini getir
    $upload = $fileManager->getUploadById($uploadId);
    if ($upload) {
        echo "Upload found: YES<br>";
        echo "Original name: " . htmlspecialchars($upload['original_name']) . "<br>";
        echo "Filename: " . htmlspecialchars($upload['filename']) . "<br>";
        
        // Dosya path kontrolü
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . $upload['filename'];
        echo "Full path: " . $fullPath . "<br>";
        echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "<br>";
        
        if (file_exists($fullPath)) {
            echo "File size: " . filesize($fullPath) . " bytes<br>";
        }
    } else {
        echo "Upload NOT found in database<br>";
    }
} else {
    echo "FileManager class NOT exists<br>";
}

// 4. Database kontrolü
echo "<h3>4. Database Kontrolü</h3>";
try {
    $stmt = $pdo->prepare("SELECT * FROM file_uploads WHERE id = ?");
    $stmt->execute([$uploadId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "Database query successful<br>";
        echo "Found upload: " . htmlspecialchars($result['original_name']) . "<br>";
    } else {
        echo "No upload found with this ID<br>";
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}

// 5. Directory kontrolü
echo "<h3>5. Upload Directory Kontrolü</h3>";
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/';
echo "Upload directory: " . $uploadDir . "<br>";
echo "Directory exists: " . (is_dir($uploadDir) ? 'YES' : 'NO') . "<br>";

if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    echo "Files in directory: " . count($files) . "<br>";
    echo "Files: " . implode(', ', array_slice($files, 2, 10)) . "<br>";
}

echo "<br><a href='uploads.php'>Back to Uploads</a>";
?>
