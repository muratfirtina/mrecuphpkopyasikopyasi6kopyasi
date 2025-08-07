<?php
/**
 * Session Debug Test - Username Hatalarını Kontrol
 */

require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>Session Debug Test</h1>";

// Session kontrolü
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Session Durumu</h2>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . session_status() . "</p>";

echo "<h2>Session Değerleri</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Anahtar</th><th>Değer</th><th>Tip</th><th>Boş mu?</th></tr>";

$sessionKeys = ['user_id', 'username', 'email', 'role', 'is_admin', 'credits', 'first_name', 'last_name', 'phone'];

foreach ($sessionKeys as $key) {
    $value = $_SESSION[$key] ?? null;
    $type = gettype($value);
    $isEmpty = empty($value) ? 'Evet' : 'Hayır';
    
    echo "<tr>";
    echo "<td><strong>$key</strong></td>";
    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
    echo "<td>$type</td>";
    echo "<td style='color: " . (empty($value) ? 'red' : 'green') . "'>$isEmpty</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>Kullanıcı Bilgileri Test</h2>";

if (isLoggedIn()) {
    echo "<p style='color: green;'>✅ Kullanıcı giriş yapmış</p>";
    
    // Test güvenli erişim
    $safeUsername = !empty($_SESSION['username']) ? $_SESSION['username'] : ($_SESSION['email'] ?? 'Kullanıcı');
    $safeEmail = $_SESSION['email'] ?? '';
    
    echo "<p><strong>Güvenli Username:</strong> " . htmlspecialchars($safeUsername) . "</p>";
    echo "<p><strong>Güvenli Email:</strong> " . htmlspecialchars($safeEmail) . "</p>";
    
    // Veritabanından kullanıcı bilgilerini kontrol et
    try {
        $stmt = $pdo->prepare("SELECT username, email, first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dbUser) {
            echo "<h3>Veritabanı Karşılaştırması</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Alan</th><th>Session</th><th>Database</th><th>Eşleşme</th></tr>";
            
            $fields = [
                'username' => [$_SESSION['username'] ?? '', $dbUser['username'] ?? ''],
                'email' => [$_SESSION['email'] ?? '', $dbUser['email'] ?? ''],
                'first_name' => [$_SESSION['first_name'] ?? '', $dbUser['first_name'] ?? ''],
                'last_name' => [$_SESSION['last_name'] ?? '', $dbUser['last_name'] ?? '']
            ];
            
            foreach ($fields as $field => $values) {
                $match = $values[0] === $values[1] ? 'Evet' : 'Hayır';
                $color = $values[0] === $values[1] ? 'green' : 'orange';
                
                echo "<tr>";
                echo "<td><strong>$field</strong></td>";
                echo "<td>" . htmlspecialchars($values[0]) . "</td>";
                echo "<td>" . htmlspecialchars($values[1]) . "</td>";
                echo "<td style='color: $color'>$match</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Session güncelleme önerisi
            if ($_SESSION['username'] !== $dbUser['username'] || empty($_SESSION['username'])) {
                echo "<h3>🔧 Session Güncelleme</h3>";
                
                if ($_POST['action'] === 'fix_session') {
                    $_SESSION['username'] = $dbUser['username'] ?: $dbUser['email'];
                    $_SESSION['email'] = $dbUser['email'];
                    $_SESSION['first_name'] = $dbUser['first_name'];
                    $_SESSION['last_name'] = $dbUser['last_name'];
                    
                    echo "<p style='color: green;'>✅ Session değerleri güncellendi!</p>";
                    echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
                } else {
                    echo "<form method='post'>";
                    echo "<input type='hidden' name='action' value='fix_session'>";
                    echo "<button type='submit' class='btn btn-primary'>Session Değerlerini Düzelt</button>";
                    echo "</form>";
                }
            }
            
        } else {
            echo "<p style='color: red;'>❌ Veritabanında kullanıcı bulunamadı!</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Veritabanı hatası: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Kullanıcı giriş yapmamış</p>";
    echo "<p><a href='login.php'>Giriş Yap</a></p>";
}

echo "<h2>PHP Error Test</h2>";

// Test undefined array key hatasını yakalama
$testOutput = "";
ob_start();
error_reporting(E_ALL);

try {
    // Bu satır hata verirse yakalayacağız
    $testUsername = htmlspecialchars($_SESSION['username']);
    echo "Username (unsafe): $testUsername<br>";
} catch (Error $e) {
    $testOutput .= "❌ Unsafe access error: " . $e->getMessage() . "<br>";
}

try {
    // Bu güvenli erişim
    $safeUsername = htmlspecialchars($_SESSION['username'] ?? $_SESSION['email'] ?? 'Kullanıcı');
    echo "✅ Username (safe): $safeUsername<br>";
} catch (Error $e) {
    $testOutput .= "Safe access error: " . $e->getMessage() . "<br>";
}

$output = ob_get_clean();
echo $output;
echo $testOutput;

echo "<h2>Çözüm Durumu</h2>";

$issues = [];

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    $issues[] = "❌ Session'da 'username' anahtarı eksik veya boş";
} else {
    echo "<p style='color: green;'>✅ Username session'da mevcut: " . htmlspecialchars($_SESSION['username']) . "</p>";
}

if (!isset($_SESSION['email']) || empty($_SESSION['email'])) {
    $issues[] = "❌ Session'da 'email' anahtarı eksik veya boş";
} else {
    echo "<p style='color: green;'>✅ Email session'da mevcut: " . htmlspecialchars($_SESSION['email']) . "</p>";
}

if (!empty($issues)) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>Tespit Edilen Sorunlar:</h3>";
    foreach ($issues as $issue) {
        echo "<p>$issue</p>";
    }
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>🎉 Tüm Session Değerleri Düzgün!</h3>";
    echo "<p>Username ve email hataları çözülmüş durumda.</p>";
    echo "</div>";
}

// Hızlı erişim linkleri
echo "<hr>";
echo "<h2>Test Linkleri</h2>";
echo "<p>";
echo "<a href='user/index.php'>Kullanıcı Dashboard</a> | ";
echo "<a href='admin/index.php'>Admin Dashboard</a> | ";
echo "<a href='login.php'>Giriş Yap</a> | ";
echo "<a href='logout.php'>Çıkış Yap</a>";
echo "</p>";

echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; }";
echo "table { width: 100%; margin: 10px 0; }";
echo "th, td { padding: 8px; text-align: left; }";
echo "th { background-color: #f2f2f2; }";
echo ".btn { background: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }";
echo ".btn:hover { background: #0056b3; }";
echo "</style>";
?>