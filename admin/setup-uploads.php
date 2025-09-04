<?php
/**
 * Uploads Directory Setup
 * Upload klasörlerini kontrol et ve oluştur
 */

$baseUploadDir = $_SERVER['DOCUMENT_ROOT'] . '<?php echo BASE_URL; ?>/uploads';
$userFilesDir = $baseUploadDir . '/user_files';
$responseFilesDir = $baseUploadDir . '/response_files';

echo "<h2>Upload Directory Setup</h2>";

// Ana uploads klasörü
if (!is_dir($baseUploadDir)) {
    mkdir($baseUploadDir, 0755, true);
    echo "Created main uploads directory: $baseUploadDir<br>";
} else {
    echo "Main uploads directory exists: $baseUploadDir<br>";
}

// user_files klasörü
if (!is_dir($userFilesDir)) {
    mkdir($userFilesDir, 0755, true);
    echo "Created user_files directory: $userFilesDir<br>";
} else {
    echo "User files directory exists: $userFilesDir<br>";
}

// response_files klasörü
if (!is_dir($responseFilesDir)) {
    mkdir($responseFilesDir, 0755, true);
    echo "Created response_files directory: $responseFilesDir<br>";
} else {
    echo "Response files directory exists: $responseFilesDir<br>";
}

// .htaccess dosyası oluştur (güvenlik için)
$htaccessContent = "Options -Indexes\n<Files ~ \"^.*\.([Hh][Tt][Aa])\">\nOrder allow,deny\nDeny from all\n</Files>";
file_put_contents($baseUploadDir . '/.htaccess', $htaccessContent);
echo "Created .htaccess file for security<br>";

echo "<br><strong>Directory permissions:</strong><br>";
echo "Base uploads: " . substr(sprintf('%o', fileperms($baseUploadDir)), -4) . "<br>";
echo "User files: " . substr(sprintf('%o', fileperms($userFilesDir)), -4) . "<br>";
echo "Response files: " . substr(sprintf('%o', fileperms($responseFilesDir)), -4) . "<br>";

echo "<br><a href='uploads.php'>Back to Uploads</a>";
?>
