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
    <title>Final Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .section { border: 1px solid #ddd; margin: 10px 0; padding: 15px; }
    </style>
</head>
<body>";

echo "<h1>🔥 Final Test</h1>";

$userId = $_SESSION['user_id'];
$fileManager = new FileManager($pdo);

echo "<div class='section'>";
echo "<h2>1. FileManager Test</h2>";
try {
    $uploads = $fileManager->getUserUploads($userId, 1, 5);
    echo "<div class='success'>✅ getUserUploads başarılı: " . count($uploads) . " dosya bulundu</div>";
    
    if (!empty($uploads)) {
        echo "<h3>Dosyalar:</h3>";
        foreach ($uploads as $upload) {
            echo "<div>- {$upload['original_name']} (Status: {$upload['status']})</div>";
        }
        
        $firstFile = $uploads[0];
        echo "<div class='section'>";
        echo "<h3>2. getUploadById Test</h3>";
        $detail = $fileManager->getUploadById($firstFile['id']);
        if ($detail) {
            echo "<div class='success'>✅ getUploadById başarılı</div>";
            echo "<div>Dosya: {$detail['original_name']}</div>";
            echo "<div>Durum: {$detail['status']}</div>";
        } else {
            echo "<div class='error'>❌ getUploadById başarısız</div>";
        }
        echo "</div>";
        
        echo "<div class='section'>";
        echo "<h3>3. downloadFile Test</h3>";
        $downloadResult = $fileManager->downloadFile($firstFile['id'], $userId, 'upload');
        if ($downloadResult['success']) {
            echo "<div class='success'>✅ downloadFile başarılı</div>";
            echo "<div>Dosya yolu: {$downloadResult['file_path']}</div>";
            echo "<div>Orijinal ad: {$downloadResult['original_name']}</div>";
            
            if (file_exists($downloadResult['file_path'])) {
                echo "<div class='success'>✅ Fiziksel dosya mevcut</div>";
            } else {
                echo "<div class='error'>❌ Fiziksel dosya yok: {$downloadResult['file_path']}</div>";
            }
        } else {
            echo "<div class='error'>❌ downloadFile hatası: {$downloadResult['message']}</div>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>4. Download Link Test</h2>";
if (!empty($uploads)) {
    $testFile = $uploads[0];
    $downloadUrl = "download.php?id=" . $testFile['id'];
    echo "<div>Test URL: <a href='$downloadUrl' target='_blank'>$downloadUrl</a></div>";
    echo "<div class='success'>✅ Download linki hazır</div>";
} else {
    echo "<div class='error'>❌ Test edilecek dosya yok</div>";
}
echo "</div>";

echo "</body></html>";
?>
