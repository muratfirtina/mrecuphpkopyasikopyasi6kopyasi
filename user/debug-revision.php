<?php
/**
 * Debug dosyası - Revize problemini çözmek için
 */

require_once '../config/config.php';
require_once '../config/database.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    die('Giriş yapmanız gerekiyor');
}

$userId = $_SESSION['user_id'];

echo "<h1>Revize Debug Sayfası</h1>";

// 1. Kullanıcının revize taleplerini listeleyelim
echo "<h2>1. Kullanıcının Revize Talepleri:</h2>";
try {
    $stmt = $pdo->prepare("SELECT id, upload_id, status, request_notes, requested_at FROM revisions WHERE user_id = ? LIMIT 5");
    $stmt->execute([$userId]);
    $revisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($revisions)) {
        echo "<p style='color: red;'>❌ Hiç revize talebiniz yok!</p>";
    } else {
        echo "<p style='color: green;'>✅ " . count($revisions) . " revize talebi bulundu.</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Upload ID</th><th>Status</th><th>Talep Tarihi</th><th>Test Link</th></tr>";
        foreach ($revisions as $rev) {
            $shortId = substr($rev['id'], 0, 8);
            echo "<tr>";
            echo "<td>" . htmlspecialchars($shortId) . "</td>";
            echo "<td>" . htmlspecialchars($rev['upload_id']) . "</td>";
            echo "<td>" . htmlspecialchars($rev['status']) . "</td>";
            echo "<td>" . date('d.m.Y H:i', strtotime($rev['requested_at'])) . "</td>";
            echo "<td><a href='revision-detail.php?id=" . htmlspecialchars($rev['id']) . "' target='_blank'>Test Et</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch(PDOException $e) {
    echo "<p style='color: red;'>❌ Database hatası: " . $e->getMessage() . "</p>";
}

// 2. revision-detail.php dosyasını test edelim
echo "<h2>2. revision-detail.php Dosya Kontrolü:</h2>";
$detailFile = __DIR__ . '/revision-detail.php';
if (file_exists($detailFile)) {
    echo "<p style='color: green;'>✅ revision-detail.php dosyası mevcut</p>";
    echo "<p>Dosya boyutu: " . filesize($detailFile) . " byte</p>";
} else {
    echo "<p style='color: red;'>❌ revision-detail.php dosyası bulunamadı!</p>";
}

// 3. Basit test linki
if (!empty($revisions)) {
    $testId = $revisions[0]['id'];
    echo "<h2>3. Test Linkleri:</h2>";
    echo "<p><a href='revision-detail.php?id=" . $testId . "' style='background: blue; color: white; padding: 10px; text-decoration: none;'>🔗 İlk Revize Detayını Aç</a></p>";
    echo "<p><a href='revision-detail.php?id=test-invalid' style='background: red; color: white; padding: 10px; text-decoration: none;'>🔗 Geçersiz ID Testi</a></p>";
}

// 4. Session bilgileri
echo "<h2>4. Session Bilgileri:</h2>";
echo "<pre>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'YOK') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'YOK') . "\n";
echo "Logged in: " . (isLoggedIn() ? 'EVET' : 'HAYIR') . "\n";
echo "</pre>";

// 5. includes kontrolü
echo "<h2>5. Include Dosyaları:</h2>";
$includeFiles = [
    '../includes/user_header.php',
    '../includes/user_footer.php',
    '../config/config.php',
    '../config/database.php'
];

foreach ($includeFiles as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ " . $file . "</p>";
    } else {
        echo "<p style='color: red;'>❌ " . $file . " BULUNAMADI!</p>";
    }
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; width: 100%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
