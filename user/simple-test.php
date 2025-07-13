<?php
echo "<h1>En Basit Test</h1>";
echo "<p>Bu sayfa açılıyor mu?</p>";
echo "<p>Tarih: " . date('Y-m-d H:i:s') . "</p>";

// Session kontrolü
session_start();
echo "<p>Session User ID: " . ($_SESSION['user_id'] ?? 'YOK') . "</p>";

// GET parametresi kontrolü
if (isset($_GET['id'])) {
    echo "<p>Gelen ID: " . htmlspecialchars($_GET['id']) . "</p>";
} else {
    echo "<p>ID parametresi yok</p>";
}

echo "<hr>";
echo "<p><a href='revisions.php'>← Revisions'a Dön</a></p>";
?>
