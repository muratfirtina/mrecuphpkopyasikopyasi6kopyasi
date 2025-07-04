<?php
/**
 * Users.php için çalışan arama testi
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Users.php Arama Testi</h1>";

// Test aramaları
$searchTerms = ['murat', 'muratfirtina', 'admin', 'hotmail'];

foreach ($searchTerms as $search) {
    echo "<h2>Arama: '$search'</h2>";
    
    $searchTerm = "%{$search}%";
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, username, email, first_name, last_name, phone, credits, role, status, created_at,
                   (SELECT COUNT(*) FROM file_uploads WHERE user_id = users.id) as total_uploads
            FROM users 
            WHERE username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?
            ORDER BY created_at DESC 
            LIMIT 50 OFFSET 0
        ");
        
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $users = $stmt->fetchAll();
        
        echo "<p>Bulunan kullanıcı sayısı: <strong>" . count($users) . "</strong></p>";
        
        if (count($users) > 0) {
            echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
            echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>First Name</th><th>Last Name</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['first_name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['last_name']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:red;'>Hiç kullanıcı bulunamadı!</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>Hata: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

echo "<br><br>";
echo "<h2>Doğru URL'ler:</h2>";
echo "<p><a href='users.php?search=murat'>users.php?search=murat</a> (0 sonuç beklenebilir)</p>";
echo "<p><a href='users.php?search=muratfirtina'>users.php?search=muratfirtina</a> (1 sonuç beklenir)</p>";
echo "<p><a href='users.php?search=admin'>users.php?search=admin</a> (1 sonuç beklenir)</p>";
echo "<p><a href='users.php?search=hotmail'>users.php?search=hotmail</a> (1 sonuç beklenir)</p>";

echo "<br><br>";
echo "<a href='users.php'>← Users sayfasına dön</a>";
?>
