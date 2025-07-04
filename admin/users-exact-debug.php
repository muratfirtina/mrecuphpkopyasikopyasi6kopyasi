<?php
/**
 * Users.php Debug - Neden Çalışmıyor?
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Users.php Debug - Neden Çalışmıyor?</h1>";

// Exact URL simülasyonu
$_GET['search'] = 'murat';

echo "<h2>URL Simülasyonu: users.php?search=murat</h2>";

// users.php'taki exact kodları çalıştır
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 50;
$search = isset($_GET['search']) ? trim($_GET['search']) : ''; // sanitize olmadan

echo "<p><strong>Page:</strong> $page</p>";
echo "<p><strong>Limit:</strong> $limit</p>";
echo "<p><strong>Search:</strong> '$search'</p>";
echo "<p><strong>Search empty?:</strong> " . (empty($search) ? 'true' : 'false') . "</p>";
echo "<p><strong>Search length:</strong> " . strlen($search) . "</p>";

if ($search) {
    echo "<p style='color:green;'>✅ IF ($search) koşulu TRUE</p>";
    
    try {
        $offset = ($page - 1) * $limit;
        $searchTerm = "%{$search}%";
        
        echo "<p><strong>Offset:</strong> $offset</p>";
        echo "<p><strong>SearchTerm:</strong> '$searchTerm'</p>";
        
        $stmt = $pdo->prepare("
            SELECT id, username, email, first_name, last_name, phone, credits, role, status, created_at,
                   (SELECT COUNT(*) FROM file_uploads WHERE user_id = users.id) as total_uploads
            FROM users 
            WHERE username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?
            ORDER BY created_at DESC 
            LIMIT $limit OFFSET $offset
        ");
        
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $users = $stmt->fetchAll();
        
        echo "<p style='color:green;'>✅ Sorgu çalıştırıldı</p>";
        echo "<p><strong>Bulunan kullanıcı sayısı:</strong> " . count($users) . "</p>";
        
        if (count($users) > 0) {
            echo "<h3>Bulunan Kullanıcılar:</h3>";
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
            echo "<p style='color:red;'>❌ HİÇ KULLANICI BULUNAMADI!</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>❌ Hata: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color:red;'>❌ IF (\$search) koşulu FALSE - Bu yüzden arama yapılmıyor!</p>";
    echo "<p>Search değeri: ";
    var_dump($search);
    echo "</p>";
}

// Sanitize fonksiyonunu test et
echo "<h2>Sanitize Fonksiyonu Test</h2>";
$test_search = 'murat';
$sanitized = sanitize($test_search);
echo "<p>Orijinal: '$test_search'</p>";
echo "<p>Sanitize edilmiş: '$sanitized'</p>";
echo "<p>Sanitize sonrası boş mu? " . (empty($sanitized) ? 'true' : 'false') . "</p>";

echo "<br><br>";
echo "<p><a href='users.php?search=murat'>← Gerçek users.php?search=murat'a git</a></p>";
?>
