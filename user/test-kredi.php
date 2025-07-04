<?php
/**
 * Kredi Testi Sayfası
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    redirect('../login.php');
}

$user = new User($pdo);
$userId = $_SESSION['user_id'];

// Session'dan kredi al
$sessionCredits = $_SESSION['credits'];

// Veritabanından kredi al
$dbCredits = $user->getUserCredits($userId);

// Session güncelle
$_SESSION['credits'] = $dbCredits;

echo "<h1>Kredi Test Sayfası</h1>";
echo "<p><strong>Kullanıcı ID:</strong> $userId</p>";
echo "<p><strong>Session Kredi (öncesi):</strong> $sessionCredits</p>";
echo "<p><strong>Veritabanı Kredi:</strong> $dbCredits</p>";
echo "<p><strong>Session Kredi (sonrası):</strong> {$_SESSION['credits']}</p>";

if ($sessionCredits != $dbCredits) {
    echo "<div style='background: yellow; padding: 10px; margin: 10px 0;'>";
    echo "<strong>UYARI:</strong> Session ve veritabanı kredileri farklı! Session güncellendi.";
    echo "</div>";
} else {
    echo "<div style='background: lightgreen; padding: 10px; margin: 10px 0;'>";
    echo "<strong>BAŞARILI:</strong> Session ve veritabanı kredileri eşleşiyor.";
    echo "</div>";
}

echo "<p><a href='index.php'>Panele Dön</a></p>";
?>
