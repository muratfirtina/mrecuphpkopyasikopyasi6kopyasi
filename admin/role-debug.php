<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

echo "<!DOCTYPE html><html><head><title>Role Debug</title></head><body>";
echo "<h2>Role Debug</h2>";

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    echo "<h3>Session Bilgileri:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<h3>Veritabanından Kullanıcı Bilgileri:</h3>";
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, role, credits FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<pre>";
            print_r($user);
            echo "</pre>";
            
            echo "<h3>Admin Kontrolleri:</h3>";
            echo "<p>isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false') . "</p>";
            echo "<p>isAdmin(): " . (isAdmin() ? 'true' : 'false') . "</p>";
            echo "<p>Session role: " . ($_SESSION['role'] ?? 'not set') . "</p>";
            echo "<p>DB role: " . ($user['role'] ?? 'not set') . "</p>";
            
            // Session'u veritabanı ile senkronize et
            if ($user['role'] !== $_SESSION['role']) {
                echo "<h3>⚠️ Session ve DB arasında farklılık var!</h3>";
                echo "<p>Session güncelleniyor...</p>";
                $_SESSION['role'] = $user['role'];
                $_SESSION['is_admin'] = ($user['role'] === 'admin') ? 1 : 0;
                echo "<p>✅ Session güncellendi.</p>";
            }
            
        } else {
            echo "<p>❌ Kullanıcı veritabanında bulunamadı!</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Hata: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p>❌ Giriş yapılmamış!</p>";
}

echo "<h3>Test Links:</h3>";
echo "<a href='credits.php'>Credits.php'ye git</a><br>";
echo "<a href='../login.php'>Login sayfasına git</a><br>";
echo "<a href='role-debug.php'>Bu sayfayı yenile</a>";

echo "</body></html>";
?>
