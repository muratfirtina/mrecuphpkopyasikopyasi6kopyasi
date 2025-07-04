<?php
/**
 * Hızlı Users Arama Test
 */

require_once '../config/config.php';
require_once '../config/database.php';

echo "<h1>Hızlı Users Arama Test</h1>";

$tests = ['murat', 'muratfirtina', 'admin', 'hotmail'];

foreach ($tests as $search) {
    echo "<h2>Test: '$search'</h2>";
    
    $searchTerm = "%{$search}%";
    
    try {
        // Test 1: Basit arama (LIMIT yok)
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE username LIKE ? OR email LIKE ?");
        $stmt->execute([$searchTerm, $searchTerm]);
        $results = $stmt->fetchAll();
        
        echo "<p><strong>Basit arama (LIMIT yok):</strong> " . count($results) . " sonuç</p>";
        
        if (count($results) > 0) {
            echo "<ul>";
            foreach ($results as $result) {
                echo "<li>" . htmlspecialchars($result['username']) . " (" . htmlspecialchars($result['email']) . ")</li>";
            }
            echo "</ul>";
        }
        
        // Test 2: LIMIT ile arama
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE username LIKE ? OR email LIKE ? LIMIT 10 OFFSET 0");
        $stmt->execute([$searchTerm, $searchTerm]);
        $limited_results = $stmt->fetchAll();
        
        echo "<p><strong>LIMIT ile arama:</strong> " . count($limited_results) . " sonuç</p>";
        
        // Test 3: users.php'taki tam sorgu
        $stmt = $pdo->prepare("
            SELECT id, username, email, first_name, last_name, phone, credits, role, status, created_at,
                   (SELECT COUNT(*) FROM file_uploads WHERE user_id = users.id) as total_uploads
            FROM users 
            WHERE username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?
            ORDER BY created_at DESC 
            LIMIT 50 OFFSET 0
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $full_results = $stmt->fetchAll();
        
        echo "<p><strong>Tam sorgu (users.php gibi):</strong> " . count($full_results) . " sonuç</p>";
        
        if (count($full_results) > 0) {
            echo "<table border='1' style='border-collapse:collapse;'>";
            echo "<tr><th>Username</th><th>Email</th><th>First Name</th><th>Last Name</th></tr>";
            foreach ($full_results as $result) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($result['username']) . "</td>";
                echo "<td>" . htmlspecialchars($result['email']) . "</td>";
                echo "<td>" . htmlspecialchars($result['first_name']) . "</td>";
                echo "<td>" . htmlspecialchars($result['last_name']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:red;'>❌ TAM SORGUDA HİÇ SONUÇ YOK!</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color:red;'>Hata: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

// Test URL'leri
echo "<h2>Test URL'leri:</h2>";
echo "<p><a href='users.php?search=murat' target='_blank'>users.php?search=murat</a></p>";
echo "<p><a href='users.php?search=muratfirtina' target='_blank'>users.php?search=muratfirtina</a></p>";
echo "<p><a href='users.php?search=admin' target='_blank'>users.php?search=admin</a></p>";

echo "<br><br>";
echo "<a href='users.php'>← Users sayfasına dön</a>";
?>
