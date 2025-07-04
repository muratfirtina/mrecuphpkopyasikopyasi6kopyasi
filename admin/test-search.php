<?php
/**
 * Kullanıcı arama testi
 */
require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Kullanıcı Arama Testi</h1>";

// 1. Tüm kullanıcıları listele
echo "<h2>1. Tüm Kullanıcılar:</h2>";
try {
    $stmt = $pdo->query("SELECT id, username, email, first_name, last_name FROM users LIMIT 10");
    $all_users = $stmt->fetchAll();
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>First Name</th><th>Last Name</th></tr>";
    foreach ($all_users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['first_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['last_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Hata: " . $e->getMessage() . "</p>";
}

// 2. 'murat' araması yap
echo "<h2>2. 'murat' Araması:</h2>";
$search = 'murat';
$searchTerm = "%{$search}%";

try {
    $stmt = $pdo->prepare("
        SELECT id, username, email, first_name, last_name 
        FROM users 
        WHERE username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $search_results = $stmt->fetchAll();
    
    echo "<p>Aranan terim: <strong>" . htmlspecialchars($search) . "</strong></p>";
    echo "<p>Bulunan sonuç sayısı: <strong>" . count($search_results) . "</strong></p>";
    
    if (count($search_results) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>First Name</th><th>Last Name</th></tr>";
        foreach ($search_results as $user) {
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
        echo "<p style='color:red;'>Hiç sonuç bulunamadı!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Arama hatası: " . $e->getMessage() . "</p>";
}

// 3. Tam arama testi
echo "<h2>3. Tam Eşleşme Testi:</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['murat']);
    $exact_match = $stmt->fetch();
    
    if ($exact_match) {
        echo "<p style='color:green;'>✅ 'murat' kullanıcısı bulundu!</p>";
        echo "<pre>" . print_r($exact_match, true) . "</pre>";
    } else {
        echo "<p style='color:red;'>❌ 'murat' kullanıcısı bulunamadı!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Tam arama hatası: " . $e->getMessage() . "</p>";
}

// 4. SQL sorgusu testi
echo "<h2>4. Manuel SQL Sorgusu:</h2>";
echo "<pre>";
echo "LIKE sorgusu: " . htmlspecialchars($searchTerm) . "\n";
echo "SQL: SELECT * FROM users WHERE username LIKE '%murat%' OR email LIKE '%murat%' OR first_name LIKE '%murat%' OR last_name LIKE '%murat%'";
echo "</pre>";

echo "<br><a href='users.php'>← Users sayfasına dön</a>";
?>
