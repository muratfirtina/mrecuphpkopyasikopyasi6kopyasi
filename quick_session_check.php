<?php
/**
 * Quick Session Check - Hızlı session kontrolü
 */

require_once 'config/config.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h1>Hızlı Session Durumu</h1>";

echo "<h2>Raw Session Data:</h2>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

echo "<h2>Specific Values:</h2>";
echo "<p><strong>user_id:</strong> " . ($_SESSION['user_id'] ?? 'NULL') . "</p>";
echo "<p><strong>username (raw):</strong> ";
var_dump($_SESSION['username'] ?? 'NOT_SET');
echo "</p>";
echo "<p><strong>email:</strong> " . ($_SESSION['email'] ?? 'NULL') . "</p>";

echo "<h2>Tests:</h2>";

// Test 1: isset
echo "<p><strong>isset(\$_SESSION['username']):</strong> " . (isset($_SESSION['username']) ? 'true' : 'false') . "</p>";

// Test 2: empty
echo "<p><strong>empty(\$_SESSION['username']):</strong> " . (empty($_SESSION['username']) ? 'true' : 'false') . "</p>";

// Test 3: is_null
echo "<p><strong>is_null(\$_SESSION['username']):</strong> " . (is_null($_SESSION['username'] ?? null) ? 'true' : 'false') . "</p>";

// Test 4: string length
$username = $_SESSION['username'] ?? '';
echo "<p><strong>strlen(username):</strong> " . strlen($username) . "</p>";

echo "<h2>What will be displayed:</h2>";
$result1 = $_SESSION['username'] ?? $_SESSION['email'] ?? 'Kullanıcı';
$result2 = !empty($_SESSION['username']) ? $_SESSION['username'] : ($_SESSION['email'] ?? 'Kullanıcı');

echo "<p><strong>Old method result:</strong> '" . htmlspecialchars($result1) . "'</p>";
echo "<p><strong>New method result:</strong> '" . htmlspecialchars($result2) . "'</p>";

echo "<h2>Database Check:</h2>";
if (isset($_SESSION['user_id'])) {
    try {
        require_once 'config/database.php';
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $dbUser = $stmt->fetch();
        
        if ($dbUser) {
            echo "<p><strong>DB Username:</strong> '" . htmlspecialchars($dbUser['username'] ?? 'NULL') . "'</p>";
            echo "<p><strong>DB Email:</strong> '" . htmlspecialchars($dbUser['email']) . "'</p>";
            
            if (empty($dbUser['username'])) {
                echo "<p style='color: red;'>⚠️ Database'de username alanı boş!</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ User not found in database</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>No user_id in session</p>";
}

if (isset($_SESSION['user_id'])) {
    echo "<form method='post' style='margin-top: 20px;'>";
    echo "<button type='submit' name='logout'>Logout & Login Again</button>";
    echo "</form>";
}

if ($_POST['logout'] ?? false) {
    session_destroy();
    echo "<p>Session cleared. <a href='login.php'>Please login again</a></p>";
}
?>