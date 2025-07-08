<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn()) {
    die('Lütfen giriş yapın');
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>File Path Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>";

echo "<h1>📂 File Path Debug</h1>";

$userId = $_SESSION['user_id'];
$fileManager = new FileManager($pdo);

// Test dosyasını al
$stmt = $pdo->prepare("SELECT * FROM file_uploads WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$file = $stmt->fetch();

if ($file) {
    echo "<h2>1. Database Info</h2>";
    echo "<div>Filename: <strong>{$file['filename']}</strong></div>";
    echo "<div>Original Name: <strong>{$file['original_name']}</strong></div>";
    echo "<div>Status: <strong>{$file['status']}</strong></div>";
    
    echo "<h2>2. Path Testing</h2>";
    echo "<div>UPLOAD_PATH: <strong>" . UPLOAD_PATH . "</strong></div>";
    
    // Farklı dosya yollarını test et
    $possiblePaths = [
        UPLOAD_PATH . $file['filename'],
        UPLOAD_PATH . 'user_files/' . $file['filename'], 
        UPLOAD_PATH . 'user_files/' . basename($file['filename']),
        dirname(UPLOAD_PATH) . '/uploads/' . $file['filename'],
        dirname(UPLOAD_PATH) . '/uploads/user_files/' . $file['filename'],
        dirname(UPLOAD_PATH) . '/uploads/user_files/' . basename($file['filename'])
    ];
    
    echo "<h3>Possible File Paths:</h3>";
    foreach ($possiblePaths as $i => $path) {
        if (file_exists($path)) {
            echo "<div class='success'>✅ Path $i: $path</div>";
        } else {
            echo "<div class='error'>❌ Path $i: $path</div>";
        }
    }
    
    echo "<h2>3. Download Test</h2>";
    $result = $fileManager->downloadFile($file['id'], $userId, 'upload');
    if ($result['success']) {
        echo "<div class='success'>✅ downloadFile başarılı</div>";
        echo "<div>Returned path: {$result['file_path']}</div>";
        
        if (file_exists($result['file_path'])) {
            echo "<div class='success'>✅ Dosya bulundu: " . filesize($result['file_path']) . " bytes</div>";
        } else {
            echo "<div class='error'>❌ Dosya yolu yanlış: {$result['file_path']}</div>";
        }
    } else {
        echo "<div class='error'>❌ downloadFile hatası: {$result['message']}</div>";
    }
    
    echo "<h2>4. Manual File Search</h2>";
    $uploadDir = dirname(UPLOAD_PATH) . '/uploads/';
    if (is_dir($uploadDir)) {
        echo "<div>Upload dizini: $uploadDir</div>";
        
        // Recursive file search
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadDir));
        $zipFiles = [];
        foreach ($iterator as $file_obj) {
            if ($file_obj->isFile() && pathinfo($file_obj->getFilename(), PATHINFO_EXTENSION) === 'zip') {
                $zipFiles[] = $file_obj->getPathname();
            }
        }
        
        echo "<h3>Found ZIP files:</h3>";
        foreach ($zipFiles as $zipFile) {
            echo "<div>📁 $zipFile</div>";
        }
    }
    
} else {
    echo "<div class='error'>❌ Dosya bulunamadı</div>";
}

echo "</body></html>";
?>
