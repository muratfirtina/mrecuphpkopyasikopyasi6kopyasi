<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn()) {
    die('Giriş yapın');
}

$userId = $_SESSION['user_id'];

echo "<h1>UUID Format Debug</h1>";

// Gerçek UUID'leri veritabanından çekelim
$stmt = $pdo->prepare("SELECT id, LENGTH(id) as id_length FROM revisions WHERE user_id = ? LIMIT 5");
$stmt->execute([$userId]);
$revisions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Veritabanından Gelen UUID'ler:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Uzunluk</th><th>Karakter Sayısı</th><th>Geçerli mi?</th></tr>";

foreach ($revisions as $rev) {
    $id = $rev['id'];
    $length = strlen($id);
    $dbLength = $rev['id_length'];
    
    // UUID validation
    $isValid = (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id) === 1);
    
    echo "<tr>";
    echo "<td style='font-family: monospace;'>" . htmlspecialchars($id) . "</td>";
    echo "<td>" . $length . " (DB: " . $dbLength . ")</td>";
    echo "<td>" . mb_strlen($id) . "</td>";
    echo "<td style='color: " . ($isValid ? 'green' : 'red') . ";'>" . ($isValid ? 'GEÇERLI' : 'GEÇERSİZ') . "</td>";
    echo "</tr>";
}
echo "</table>";

// En kolay çözüm: UUID kontrolünü kaldıralım
echo "<h2>Çözüm Test Linki:</h2>";
if (!empty($revisions)) {
    $testId = $revisions[0]['id'];
    echo "<p><a href='revision-detail-nocheck.php?id=" . urlencode($testId) . "' style='background: green; color: white; padding: 10px; text-decoration: none;'>🔗 UUID Kontrolsüz Test</a></p>";
}

?>

<style>
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
