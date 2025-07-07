<?php
/**
 * Database Table Structure Debug
 * Veritabanı yapısını analiz etmek için
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Admin kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized access');
}

echo "<!DOCTYPE html>
<html lang='tr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Structure Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>";

echo "<h1>🔍 Database Structure Debug</h1>";

// 1. file_uploads tablosu yapısı
echo "<div class='section'>";
echo "<h2>1. file_uploads Tablosu Yapısı</h2>";
try {
    $stmt = $pdo->query("DESCRIBE file_uploads");
    $columns = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Sample data
    $stmt = $pdo->query("SELECT id, user_id, original_name, filename, file_size, status, upload_date FROM file_uploads ORDER BY upload_date DESC LIMIT 3");
    $samples = $stmt->fetchAll();
    
    if (!empty($samples)) {
        echo "<h3>Sample Data (Son 3 kayıt):</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>User ID</th><th>Original Name</th><th>Filename</th><th>Size</th><th>Status</th></tr>";
        foreach ($samples as $sample) {
            echo "<tr>";
            echo "<td>" . substr($sample['id'], 0, 8) . "...</td>";
            echo "<td>" . substr($sample['user_id'], 0, 8) . "...</td>";
            echo "<td>{$sample['original_name']}</td>";
            echo "<td>{$sample['filename']}</td>";
            echo "<td>" . formatFileSize($sample['file_size']) . "</td>";
            echo "<td>{$sample['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 2. file_responses tablosu yapısı
echo "<div class='section'>";
echo "<h2>2. file_responses Tablosu Yapısı</h2>";
try {
    $stmt = $pdo->query("DESCRIBE file_responses");
    $columns = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Sample data
    $stmt = $pdo->query("SELECT id, upload_id, admin_id, filename, file_size, upload_date FROM file_responses ORDER BY upload_date DESC LIMIT 3");
    $samples = $stmt->fetchAll();
    
    if (!empty($samples)) {
        echo "<h3>Sample Data (Son 3 kayıt):</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Upload ID</th><th>Admin ID</th><th>Filename</th><th>File Size</th><th>Upload Date</th></tr>";
        foreach ($samples as $sample) {
            echo "<tr>";
            echo "<td>" . substr($sample['id'], 0, 8) . "...</td>";
            echo "<td>" . substr($sample['upload_id'] ?? '', 0, 8) . "...</td>";
            echo "<td>" . substr($sample['admin_id'] ?? '', 0, 8) . "...</td>";
            echo "<td>{$sample['filename']}</td>";
            echo "<td>" . formatFileSize($sample['file_size']) . "</td>";
            echo "<td>{$sample['upload_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 3. Dosya sistemini kontrol et
echo "<div class='section'>";
echo "<h2>3. Dosya Sistemi Kontrolü</h2>";

$uploadPaths = [
    'uploads/' => $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/',
    'uploads/user_files/' => $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/',
    'uploads/response_files/' => $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/response_files/',
];

foreach ($uploadPaths as $name => $path) {
    if (is_dir($path)) {
        $fileCount = count(glob($path . '*', GLOB_BRACE));
        echo "<p class='success'>✅ $name - Mevcut ($fileCount item)</p>";
    } else {
        echo "<p class='error'>❌ $name - Bulunamadı: $path</p>";
    }
}

// 4. Gerçek dosya path'lerini kontrol et
echo "<h3>Gerçek Dosya Path Kontrolü:</h3>";
try {
    $stmt = $pdo->query("SELECT id, original_name, filename FROM file_uploads WHERE filename IS NOT NULL LIMIT 5");
    $files = $stmt->fetchAll();
    
    foreach ($files as $file) {
        // filename'den tam path oluştur
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/mrecuphpkopyasikopyasi6kopyasi/uploads/user_files/' . $file['filename'];
        
        $exists = file_exists($fullPath);
        $status = $exists ? "<span class='success'>✅ Var</span>" : "<span class='error'>❌ Yok</span>";
        
        echo "<p><strong>{$file['original_name']}</strong><br>";
        echo "DB Filename: {$file['filename']}<br>";
        echo "Full Path: $fullPath<br>";
        echo "Status: $status</p><hr>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "</body></html>";
?>
