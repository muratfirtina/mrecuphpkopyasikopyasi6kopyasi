<?php
/**
 * Users Arama Debug - Detaylı Test
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Users Arama Problemi Debug</h1>";

// Test URL'si: users.php?search=murat
$search = 'murat';

echo "<h2>Debug Bilgileri</h2>";
echo "<p>Aranan kelime: <strong>$search</strong></p>";
echo "<p>Search Term: <strong>%{$search}%</strong></p>";

// 1. Önce veritabanındaki tüm kullanıcıları listele
echo "<h2>1. Veritabanındaki Tüm Kullanıcılar</h2>";
try {
    $stmt = $pdo->query("SELECT id, username, email, first_name, last_name FROM users");
    $all_users = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>First Name</th><th>Last Name</th><th>Murat İçeriyor mu?</th></tr>";
    
    foreach ($all_users as $user) {
        $contains_murat = (
            stripos($user['username'], 'murat') !== false ||
            stripos($user['email'], 'murat') !== false ||
            stripos($user['first_name'], 'murat') !== false ||
            stripos($user['last_name'], 'murat') !== false
        );
        
        echo "<tr" . ($contains_murat ? " style='background-color: #ffffcc;'" : "") . ">";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['first_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['last_name']) . "</td>";
        echo "<td>" . ($contains_murat ? "✅ EVET" : "❌ Hayır") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Hata: " . $e->getMessage() . "</p>";
}

// 2. Şimdi tam olarak users.php'taki arama kodunu test et
echo "<h2>2. Users.php Arama Kodu Test</h2>";

$page = 1;
$limit = 50;
$offset = ($page - 1) * $limit;
$searchTerm = "%{$search}%";

echo "<p>Sayfa: $page</p>";
echo "<p>Limit: $limit</p>";
echo "<p>Offset: $offset</p>";
echo "<p>Search Term: $searchTerm</p>";

try {
    $stmt = $pdo->prepare("
        SELECT id, username, email, first_name, last_name, phone, credits, role, status, created_at,
               (SELECT COUNT(*) FROM file_uploads WHERE user_id = users.id) as total_uploads
        FROM users 
        WHERE username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    
    echo "<p><strong>SQL Sorgusu:</strong></p>";
    echo "<pre>
SELECT id, username, email, first_name, last_name, phone, credits, role, status, created_at,
       (SELECT COUNT(*) FROM file_uploads WHERE user_id = users.id) as total_uploads
FROM users 
WHERE username LIKE '%murat%' OR email LIKE '%murat%' OR first_name LIKE '%murat%' OR last_name LIKE '%murat%'
ORDER BY created_at DESC 
LIMIT 50 OFFSET 0
</pre>";
    
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
    $users = $stmt->fetchAll();
    
    echo "<p><strong>Bulunan kullanıcı sayısı: " . count($users) . "</strong></p>";
    
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
        echo "<p style='color:red; font-size:20px;'>❌ HİÇ KULLANICI BULUNAMADI!</p>";
        echo "<p style='color:red;'>Bu büyük bir sorun! 'muratfirtina' kullanıcısı 'murat' aramasında çıkmalı.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Arama hatası: " . $e->getMessage() . "</p>";
}

// 3. Manuel LIKE testleri
echo "<h2>3. Manuel LIKE Testleri</h2>";

$tests = [
    "username LIKE '%murat%'" => "SELECT * FROM users WHERE username LIKE '%murat%'",
    "email LIKE '%murat%'" => "SELECT * FROM users WHERE email LIKE '%murat%'", 
    "first_name LIKE '%murat%'" => "SELECT * FROM users WHERE first_name LIKE '%murat%'",
    "last_name LIKE '%murat%'" => "SELECT * FROM users WHERE last_name LIKE '%murat%'"
];

foreach ($tests as $desc => $sql) {
    try {
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll();
        echo "<p><strong>$desc:</strong> " . count($results) . " sonuç</p>";
        
        if (count($results) > 0) {
            foreach ($results as $result) {
                echo "<p style='margin-left: 20px; color: green;'>✅ " . 
                     htmlspecialchars($result['username']) . " (" . 
                     htmlspecialchars($result['email']) . ")</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>$desc - Hata: " . $e->getMessage() . "</p>";
    }
}

// 4. Users.php'nin tam URL'ini test et
echo "<h2>4. Test URL'leri</h2>";
echo "<p><a href='users.php?search=murat' target='_blank'>users.php?search=murat (YENİ SEKMEDE AÇ)</a></p>";
echo "<p><a href='users.php?search=muratfirtina' target='_blank'>users.php?search=muratfirtina (YENİ SEKMEDE AÇ)</a></p>";

// 5. PHP ve MySQL charset kontrolü
echo "<h2>5. Charset ve Collation Kontrolü</h2>";

try {
    $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'users'");
    $table_info = $stmt->fetch();
    if ($table_info) {
        echo "<p>Users tablosu Collation: " . $table_info['Collation'] . "</p>";
    }
    
    $stmt = $pdo->query("SHOW FULL COLUMNS FROM users WHERE Field IN ('username', 'email', 'first_name', 'last_name')");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1'>";
    echo "<tr><th>Column</th><th>Type</th><th>Collation</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . ($col['Collation'] ?: 'default') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Charset kontrolü hatası: " . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<a href='users.php'>← Users sayfasına dön</a>";
?>
