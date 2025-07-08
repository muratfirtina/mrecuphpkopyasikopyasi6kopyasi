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
    <title>User ID Debug</title>
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

echo "<h1>🔍 User ID Debug</h1>";

$currentUserId = $_SESSION['user_id'];
echo "<h2>1. Current User Info</h2>";
echo "<div>Session User ID: <strong>$currentUserId</strong></div>";

try {
    // Current user bilgilerini al
    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
    $stmt->execute([$currentUserId]);
    $currentUser = $stmt->fetch();
    
    if ($currentUser) {
        echo "<div class='success'>✅ Current user bulundu: {$currentUser['username']} ({$currentUser['email']})</div>";
    } else {
        echo "<div class='error'>❌ Current user bulunamadı!</div>";
    }
    
    echo "<h2>2. Tüm Dosyalar (User ID ile)</h2>";
    $stmt = $pdo->query("SELECT id, user_id, original_name, status, upload_date FROM file_uploads ORDER BY upload_date DESC");
    $allFiles = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>File ID</th><th>User ID</th><th>Dosya Adı</th><th>Durum</th><th>Tarih</th><th>Eşleşme</th></tr>";
    
    foreach ($allFiles as $file) {
        $match = $file['user_id'] === $currentUserId ? '✅' : '❌';
        $userIdShort = substr($file['user_id'], 0, 8) . '...';
        $fileIdShort = substr($file['id'], 0, 8) . '...';
        
        echo "<tr>";
        echo "<td>$fileIdShort</td>";
        echo "<td>$userIdShort</td>";
        echo "<td>{$file['original_name']}</td>";
        echo "<td>{$file['status']}</td>";
        echo "<td>{$file['upload_date']}</td>";
        echo "<td>$match</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>3. User ID Eşleşmesi</h2>";
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM file_uploads WHERE user_id = ?");
    $stmt->execute([$currentUserId]);
    $userFileCount = $stmt->fetch()['count'];
    
    echo "<div>Current user'ın dosya sayısı: <strong>$userFileCount</strong></div>";
    
    if ($userFileCount == 0) {
        echo "<div class='warning'>⚠️ Bu kullanıcının hiç dosyası yok!</div>";
        
        // Dosyası olan kullanıcıları listele
        echo "<h3>Dosyası Olan Kullanıcılar:</h3>";
        $stmt = $pdo->query("
            SELECT u.id, u.username, u.email, COUNT(fu.id) as file_count
            FROM users u
            LEFT JOIN file_uploads fu ON u.id = fu.user_id
            GROUP BY u.id
            HAVING file_count > 0
        ");
        $usersWithFiles = $stmt->fetchAll();
        
        echo "<table>";
        echo "<tr><th>User ID</th><th>Username</th><th>Email</th><th>Dosya Sayısı</th></tr>";
        foreach ($usersWithFiles as $user) {
            $userIdShort = substr($user['id'], 0, 8) . '...';
            echo "<tr>";
            echo "<td>$userIdShort</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['file_count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='success'>✅ Bu kullanıcının $userFileCount dosyası var</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Hata: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
