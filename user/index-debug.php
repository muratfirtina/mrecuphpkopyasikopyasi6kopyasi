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
    <title>Index Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>";

echo "<h1>📊 Index Debug</h1>";

$userId = $_SESSION['user_id'];
$fileManager = new FileManager($pdo);

echo "<h2>1. Doğrudan SQL Sorgusu</h2>";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM file_uploads WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalUploads = $stmt->fetchColumn();
    echo "<div class='success'>✅ SQL Total: $totalUploads</div>";
    
    // Dosya detayları
    $stmt = $pdo->prepare("SELECT original_name, status, upload_date FROM file_uploads WHERE user_id = ?");
    $stmt->execute([$userId]);
    $files = $stmt->fetchAll();
    
    echo "<h3>SQL ile Dosyalar:</h3>";
    echo "<table>";
    echo "<tr><th>Dosya Adı</th><th>Durum</th><th>Tarih</th></tr>";
    foreach ($files as $file) {
        echo "<tr>";
        echo "<td>{$file['original_name']}</td>";
        echo "<td>{$file['status']}</td>";
        echo "<td>{$file['upload_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ SQL Hatası: " . $e->getMessage() . "</div>";
}

echo "<h2>2. FileManager getUserUploads</h2>";
try {
    $userUploads = $fileManager->getUserUploads($userId, 1, 10);
    echo "<div class='success'>✅ FileManager Total: " . count($userUploads) . "</div>";
    
    echo "<h3>FileManager ile Dosyalar:</h3>";
    echo "<table>";
    echo "<tr><th>Dosya Adı</th><th>Durum</th><th>Tarih</th></tr>";
    foreach ($userUploads as $file) {
        echo "<tr>";
        echo "<td>{$file['original_name']}</td>";
        echo "<td>{$file['status']}</td>";
        echo "<td>{$file['upload_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ FileManager Hatası: " . $e->getMessage() . "</div>";
}

echo "<h2>3. User ID Kontrolü</h2>";
echo "<div>Current User ID: <strong>$userId</strong></div>";

echo "</body></html>";
?>
