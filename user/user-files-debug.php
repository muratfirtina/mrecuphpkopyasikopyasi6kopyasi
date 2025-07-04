<?php
/**
 * User Files Debug
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/FileManager.php';

if (!isLoggedIn()) {
    die("Giriş yapmanız gerekiyor!");
}

echo "<h1>🔍 User Files Debug</h1>";

$fileManager = new FileManager($pdo);
$userId = $_SESSION['user_id'];

echo "<h2>1. Session Bilgileri:</h2>";
echo "<p>User ID: {$userId}</p>";
echo "<p>Username: {$_SESSION['username']}</p>";

echo "<h2>2. Kullanıcının dosyalarını direkt SQL ile kontrol:</h2>";
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM file_uploads WHERE user_id = ?");
$stmt->execute([$userId]);
$count = $stmt->fetch()['count'];
echo "<p>Kullanıcının toplam dosya sayısı: <strong>{$count}</strong></p>";

if ($count > 0) {
    $stmt = $pdo->prepare("SELECT id, original_name, status, upload_date FROM file_uploads WHERE user_id = ? ORDER BY upload_date DESC");
    $stmt->execute([$userId]);
    $userFiles = $stmt->fetchAll();
    
    echo "<h3>Kullanıcının dosyaları:</h3>";
    echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
    echo "<tr><th>ID</th><th>Dosya Adı</th><th>Durum</th><th>Tarih</th></tr>";
    foreach ($userFiles as $file) {
        echo "<tr>";
        echo "<td>{$file['id']}</td>";
        echo "<td>{$file['original_name']}</td>";
        echo "<td>{$file['status']}</td>";
        echo "<td>{$file['upload_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>3. getUserUploads() metodu test:</h2>";
$uploads = $fileManager->getUserUploads($userId, 1, 20);
echo "<p>getUserUploads sonuç sayısı: <strong>" . count($uploads) . "</strong></p>";

if (!empty($uploads)) {
    echo "<h3>getUserUploads sonucu:</h3>";
    echo "<pre>" . print_r($uploads[0], true) . "</pre>";
} else {
    echo "<p style='color:red;'>❌ getUserUploads boş array döndü</p>";
    
    // Manuel JOIN sorgusu test et
    echo "<h3>Manuel JOIN Testi:</h3>";
    $stmt = $pdo->prepare("
        SELECT fu.*, b.name as brand_name, m.name as model_name,
               0 as has_response, 0 as response_id
        FROM file_uploads fu
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        WHERE fu.user_id = ?
        ORDER BY fu.upload_date DESC
    ");
    $stmt->execute([$userId]);
    $manualResults = $stmt->fetchAll();
    
    echo "<p>Manuel JOIN sonuç sayısı: <strong>" . count($manualResults) . "</strong></p>";
    
    if (!empty($manualResults)) {
        echo "<pre>" . print_r($manualResults[0], true) . "</pre>";
    }
}

echo "<h2>4. Brands ve Models tabloları kontrol:</h2>";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM brands");
$brandCount = $stmt->fetch()['count'];
echo "<p>Brands tablosu: {$brandCount} kayıt</p>";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM models");
$modelCount = $stmt->fetch()['count'];
echo "<p>Models tablosu: {$modelCount} kayıt</p>";

// Dosya detaylarını kontrol et
if ($count > 0) {
    echo "<h2>5. İlk dosyanın detayları:</h2>";
    $stmt = $pdo->prepare("
        SELECT fu.*, b.name as brand_name, m.name as model_name
        FROM file_uploads fu
        LEFT JOIN brands b ON fu.brand_id = b.id
        LEFT JOIN models m ON fu.model_id = m.id
        WHERE fu.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $fileDetail = $stmt->fetch();
    
    if ($fileDetail) {
        echo "<table border='1' style='border-collapse:collapse;'>";
        foreach ($fileDetail as $key => $value) {
            if (!is_numeric($key)) {
                echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
            }
        }
        echo "</table>";
    }
}

echo "<br><hr><br>";
echo "<a href='files.php'>📁 User Files sayfasına git</a>";
?>
